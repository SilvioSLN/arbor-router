<?php

declare(strict_types=1);

namespace Arbor\Router\Validation;

/**
 * Interface para regras de validação customizáveis.
 *
 * Implementações devem ser stateless e thread-safe.
 *
 * @package Arbor\Router\Validation
 */
interface RuleInterface
{
    /**
     * Valida um valor contra esta regra.
     *
     * @param mixed $value Valor a validar
     * @param array<int, string> $parameters Parâmetros da regra (ex: ['3'] para min:3)
     * @param string $field Nome do campo sendo validado
     * @param array<string, mixed> $allData Todos os dados sendo validados (para regras cross-field)
     * @return bool True se válido
     */
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool;

    /**
     * Retorna a mensagem de erro padrão.
     *
     * Placeholders suportados:
     * - :field → Nome do campo
     * - :param0, :param1, etc → Parâmetros da regra
     *
     * @return string Mensagem de erro
     */
    public function message(): string;

    /**
     * Retorna o nome da regra.
     *
     * @return string Nome (ex: 'required', 'email', 'min')
     */
    public function name(): string;
}
