<?php

declare(strict_types=1);

namespace Arbor\Router\Validation\Rules;

use Arbor\Router\Validation\RuleInterface;

/**
 * Valida números de telefone brasileiros.
 *
 * Aceita formatos: (11)91234-5678, 11912345678, +5511912345678
 */
class PhoneRule implements RuleInterface
{
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool
    {
        if (!is_string($value)) return false;
        $cleaned = preg_replace('/[\s\-\(\)\+]/', '', $value);
        // Formato brasileiro: 10-11 dígitos (com DDD), ou 12-13 com código país
        return (bool) preg_match('/^(\d{10,11}|55\d{10,11})$/', $cleaned);
    }

    public function message(): string { return 'The :field field must be a valid phone number.'; }
    public function name(): string { return 'phone'; }
}
