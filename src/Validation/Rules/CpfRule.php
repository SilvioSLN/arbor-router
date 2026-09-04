<?php

declare(strict_types=1);

namespace Arbor\Router\Validation\Rules;

use Arbor\Router\Validation\RuleInterface;

/**
 * Valida CPF brasileiro.
 *
 * Implementa o algoritmo oficial de validação com dígitos verificadores.
 * Aceita formatos: 123.456.789-09 ou 12345678909
 */
class CpfRule implements RuleInterface
{
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool
    {
        if (!is_string($value)) return false;

        // Remove caracteres não numéricos
        $cpf = preg_replace('/\D/', '', $value);

        if (strlen($cpf) !== 11) return false;

        // Rejeita CPFs com todos os dígitos iguais (ex: 111.111.111-11)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        // Calcula primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cpf[9] !== $digit1) return false;

        // Calcula segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cpf[10] === $digit2;
    }

    public function message(): string { return 'The :field field must be a valid CPF.'; }
    public function name(): string { return 'cpf'; }
}
