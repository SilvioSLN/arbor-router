<?php

declare(strict_types=1);

namespace Arbor\Router\Sanitizer;

/**
 * Interface para motor de sanitização de dados.
 *
 * @package Arbor\Router\Sanitizer
 */
interface SanitizerInterface
{
    /**
     * Sanitiza os dados fornecidos aplicando os filtros.
     *
     * @param array<string, mixed> $data Dados a sanitizar
     * @param array<string, string|string[]> $rules Regras de sanitização
     * @return array<string, mixed> Dados sanitizados
     */
    public function sanitize(array $data, array $rules): array;
}
