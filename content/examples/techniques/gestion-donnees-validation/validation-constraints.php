<?php

namespace App\Technical\DataManagement\Validation\Constraint;

use App\Technical\DataManagement\Validation\Result\ConstraintViolation;
use App\Technical\DataManagement\Validation\Result\ValidationResult;

// ✅ Contraintes de Validation Hive (Projet Hive)
abstract class Constraint
{
    public function validate(mixed $value, string $propertyPath): array
    {
        $violations = [];
        
        if (!$this->isValid($value)) {
            $violations[] = new ConstraintViolation(
                $this->getMessage(),
                $propertyPath,
                $value,
                $this->getCode()
            );
        }
        
        return $violations;
    }
    
    abstract protected function isValid(mixed $value): bool;
    abstract protected function getMessage(): string;
    abstract protected function getCode(): string;
}

// Contrainte pour les types
final class Type extends Constraint
{
    public function __construct(private string $expectedType) {}
    
    protected function isValid(mixed $value): bool
    {
        return match ($this->expectedType) {
            'string' => is_string($value),
            'int' => is_int($value),
            'float' => is_float($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'null' => is_null($value),
            default => false
        };
    }
    
    protected function getMessage(): string
    {
        return "Expected {$this->expectedType}, got " . gettype($value);
    }
    
    protected function getCode(): string
    {
        return 'INVALID_TYPE';
    }
}

// Contrainte pour les valeurs non vides
final class NotBlank extends Constraint
{
    protected function isValid(mixed $value): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        
        if (is_array($value)) {
            return !empty($value);
        }
        
        return !is_null($value);
    }
    
    protected function getMessage(): string
    {
        return 'This value should not be blank';
    }
    
    protected function getCode(): string
    {
        return 'NOT_BLANK';
    }
}

// Contrainte pour la longueur
final class Length extends Constraint
{
    public function __construct(
        private ?int $min = null,
        private ?int $max = null,
        private ?int $exact = null
    ) {}
    
    protected function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        $length = strlen($value);
        
        if ($this->exact !== null) {
            return $length === $this->exact;
        }
        
        if ($this->min !== null && $length < $this->min) {
            return false;
        }
        
        if ($this->max !== null && $length > $this->max) {
            return false;
        }
        
        return true;
    }
    
    protected function getMessage(): string
    {
        if ($this->exact !== null) {
            return "This value should have exactly {$this->exact} characters";
        }
        
        $message = 'This value should have';
        
        if ($this->min !== null && $this->max !== null) {
            $message .= " between {$this->min} and {$this->max} characters";
        } elseif ($this->min !== null) {
            $message .= " at least {$this->min} characters";
        } elseif ($this->max !== null) {
            $message .= " at most {$this->max} characters";
        }
        
        return $message;
    }
    
    protected function getCode(): string
    {
        return 'INVALID_LENGTH';
    }
}

// Contrainte pour les emails
final class Email extends Constraint
{
    protected function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    protected function getMessage(): string
    {
        return 'This value is not a valid email address';
    }
    
    protected function getCode(): string
    {
        return 'INVALID_EMAIL';
    }
}

// Contrainte pour les UUIDs
final class Uuid extends Constraint
{
    protected function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }
    
    protected function getMessage(): string
    {
        return 'This value is not a valid UUID';
    }
    
    protected function getCode(): string
    {
        return 'INVALID_UUID';
    }
}

// Contrainte pour les choix
final class Choice extends Constraint
{
    public function __construct(private array $choices) {}
    
    protected function isValid(mixed $value): bool
    {
        return in_array($value, $this->choices, true);
    }
    
    protected function getMessage(): string
    {
        return 'This value is not a valid choice';
    }
    
    protected function getCode(): string
    {
        return 'INVALID_CHOICE';
    }
}

// Contrainte pour les expressions régulières
final class Regex extends Constraint
{
    public function __construct(private string $pattern) {}
    
    protected function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        return preg_match($this->pattern, $value) === 1;
    }
    
    protected function getMessage(): string
    {
        return 'This value does not match the required pattern';
    }
    
    protected function getCode(): string
    {
        return 'INVALID_PATTERN';
    }
}

// Contrainte pour les dates
final class DateTime extends Constraint
{
    public function __construct(
        private string $format = 'Y-m-d H:i:s',
        private ?string $timezone = null
    ) {}
    
    protected function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        $date = \DateTime::createFromFormat($this->format, $value);
        
        if (!$date) {
            return false;
        }
        
        if ($this->timezone) {
            $date->setTimezone(new \DateTimeZone($this->timezone));
        }
        
        return $date->format($this->format) === $value;
    }
    
    protected function getMessage(): string
    {
        return "This value is not a valid date in format {$this->format}";
    }
    
    protected function getCode(): string
    {
        return 'INVALID_DATETIME';
    }
}

// Contrainte pour les valeurs numériques
final class Numeric extends Constraint
{
    public function __construct(
        private ?float $min = null,
        private ?float $max = null
    ) {}
    
    protected function isValid(mixed $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        
        $numericValue = (float) $value;
        
        if ($this->min !== null && $numericValue < $this->min) {
            return false;
        }
        
        if ($this->max !== null && $numericValue > $this->max) {
            return false;
        }
        
        return true;
    }
    
    protected function getMessage(): string
    {
        $message = 'This value should be numeric';
        
        if ($this->min !== null && $this->max !== null) {
            $message .= " between {$this->min} and {$this->max}";
        } elseif ($this->min !== null) {
            $message .= " greater than or equal to {$this->min}";
        } elseif ($this->max !== null) {
            $message .= " less than or equal to {$this->max}";
        }
        
        return $message;
    }
    
    protected function getCode(): string
    {
        return 'INVALID_NUMERIC';
    }
}
