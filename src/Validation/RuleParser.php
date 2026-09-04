<?php

declare(strict_types=1);

namespace Arbor\Router\Validation;

/**
 * Parser de regras de validação no formato pipe string.
 *
 * Converte strings como 'required|email|min:3' em arrays de regras
 * com seus nomes e parâmetros.
 *
 * @package Arbor\Router\Validation
 */
class RuleParser
{
    /**
     * Parseia uma string de regras separadas por pipe.
     *
     * @param string|string[] $rules Regras como string pipe ou array
     * @return array<int, array{name: string, parameters: string[]}> Regras parseadas
     *
     * Exemplos:
     *  - 'required|email' → [['name' => 'required', 'parameters' => []], ['name' => 'email', 'parameters' => []]]
     *  - 'min:3|max:100' → [['name' => 'min', 'parameters' => ['3']], ['name' => 'max', 'parameters' => ['100']]]
     *  - 'in:a,b,c' → [['name' => 'in', 'parameters' => ['a', 'b', 'c']]]
     */
    public function parse(string|array $rules): array
    {
        if (is_array($rules)) {
            $ruleStrings = $rules;
        } else {
            $ruleStrings = explode('|', $rules);
        }

        $parsed = [];

        foreach ($ruleStrings as $rule) {
            $rule = trim($rule);
            if ($rule === '') {
                continue;
            }

            // Separa nome dos parâmetros (ex: 'min:3' → ['min', '3'])
            $colonPos = strpos($rule, ':');

            if ($colonPos === false) {
                $parsed[] = [
                    'name' => $rule,
                    'parameters' => [],
                ];
            } else {
                $name = substr($rule, 0, $colonPos);
                $paramString = substr($rule, $colonPos + 1);
                $parameters = explode(',', $paramString);

                $parsed[] = [
                    'name' => $name,
                    'parameters' => $parameters,
                ];
            }
        }

        return $parsed;
    }

    /**
     * Expande regras com dot-notation para arrays aninhados.
     *
     * Converte:
     *  'items.*.name' => 'required|string'
     * Em validações individuais para cada item do array.
     *
     * @param string $field Campo com dot-notation
     * @param array<string, mixed> $data Dados completos
     * @return array<string, mixed> Mapa de campo expandido → valor
     */
    public function expandDotNotation(string $field, array $data): array
    {
        $parts = explode('.', $field);
        return $this->expandParts($parts, $data, '');
    }

    /**
     * Expande recursivamente partes de um campo com dot-notation.
     *
     * @param string[] $parts Partes restantes do campo
     * @param mixed $data Dados no nível atual
     * @param string $prefix Prefixo do campo atual
     * @return array<string, mixed> Mapa de campos expandidos
     */
    private function expandParts(array $parts, mixed $data, string $prefix): array
    {
        if (empty($parts)) {
            return [$prefix => $data];
        }

        $current = array_shift($parts);
        $fullKey = $prefix === '' ? $current : $prefix . '.' . $current;

        if ($current === '*') {
            // Wildcard — expande para cada índice do array
            if (!is_array($data)) {
                return [$fullKey => null];
            }

            $result = [];
            foreach ($data as $index => $item) {
                $indexKey = $prefix === '' ? (string) $index : $prefix . '.' . $index;
                $expanded = $this->expandParts($parts, $item, $indexKey);
                $result = array_merge($result, $expanded);
            }
            return $result;
        }

        // Chave literal
        $value = is_array($data) ? ($data[$current] ?? null) : null;

        if (empty($parts)) {
            return [$fullKey => $value];
        }

        return $this->expandParts($parts, $value, $fullKey);
    }
}
