<?php

declare(strict_types=1);

namespace Arbor\Router\Validation\Rules;

use Arbor\Router\Validation\RuleInterface;

/**
 * Valida CNPJ brasileiro.
 *
 * Implementa o algoritmo oficial de validação com dígitos verificadores.
 * Aceita formatos: 12.345.678/0001-95 ou 12345678000195
 */
class CnpjRule implements RuleInterface
{
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool
    {
        if (!is_string($value)) return false;

        $cnpj = preg_replace('/\D/', '', $value);

        if (strlen($cnpj) !== 14) return false;
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

        // Primeiro dígito verificador
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cnpj[12] !== $digit1) return false;

        // Segundo dígito verificador
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cnpj[13] === $digit2;
    }

    public function message(): string { return 'The :field field must be a valid CNPJ.'; }
    public function name(): string { return 'cnpj'; }
}
