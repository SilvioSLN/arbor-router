<?php

declare(strict_types=1);

namespace Arbor\Router\Routing;

/**
 * Parser de segmentos de caminho na árvore de diretórios.
 *
 * Interpreta nomes de pastas e classifica cada segmento em um dos tipos:
 *
 * - **Estático:** `users` → casa literalmente com "users" na URL
 * - **Dinâmico:** `[id]` → casa com qualquer segmento e captura como parâmetro
 * - **Catch-all:** `[...slug]` → casa com um ou mais segmentos restantes
 * - **Optional catch-all:** `[[...slug]]` → casa com zero ou mais segmentos restantes
 * - **Route group:** `(admin)` → ignorado na URL, mas permite agrupamento de layouts
 *
 * O parser é stateless e opera sobre segmentos individuais.
 *
 * @package Arbor\Router\Routing
 */
class SegmentParser
{
    /**
     * Tipo de segmento: estático.
     */
    public const TYPE_STATIC = 'static';

    /**
     * Tipo de segmento: parâmetro dinâmico [param].
     */
    public const TYPE_DYNAMIC = 'dynamic';

    /**
     * Tipo de segmento: catch-all [...param].
     */
    public const TYPE_CATCH_ALL = 'catch_all';

    /**
     * Tipo de segmento: optional catch-all [[...param]].
     */
    public const TYPE_OPTIONAL_CATCH_ALL = 'optional_catch_all';

    /**
     * Tipo de segmento: route group (group).
     */
    public const TYPE_GROUP = 'group';

    /**
     * Analisa um segmento de diretório e retorna seu tipo e nome.
     *
     * @param string $segment Nome do diretório/segmento
     * @return array{type: string, name: string} Tipo do segmento e nome do parâmetro
     *
     * Exemplos:
     *  - `parse('users')` → `['type' => 'static', 'name' => 'users']`
     *  - `parse('[id]')` → `['type' => 'dynamic', 'name' => 'id']`
     *  - `parse('[...slug]')` → `['type' => 'catch_all', 'name' => 'slug']`
     *  - `parse('[[...slug]]')` → `['type' => 'optional_catch_all', 'name' => 'slug']`
     *  - `parse('(admin)')` → `['type' => 'group', 'name' => 'admin']`
     */
    public static function parse(string $segment): array
    {
        // Optional catch-all: [[...slug]]
        if (preg_match('/^\[\[\.\.\.(\w+)\]\]$/', $segment, $matches)) {
            return [
                'type' => self::TYPE_OPTIONAL_CATCH_ALL,
                'name' => $matches[1],
            ];
        }

        // Catch-all: [...slug]
        if (preg_match('/^\[\.\.\.(\w+)\]$/', $segment, $matches)) {
            return [
                'type' => self::TYPE_CATCH_ALL,
                'name' => $matches[1],
            ];
        }

        // Dynamic: [id]
        if (preg_match('/^\[(\w+)\]$/', $segment, $matches)) {
            return [
                'type' => self::TYPE_DYNAMIC,
                'name' => $matches[1],
            ];
        }

        // Route group: (admin)
        if (preg_match('/^\((\w+)\)$/', $segment, $matches)) {
            return [
                'type' => self::TYPE_GROUP,
                'name' => $matches[1],
            ];
        }

        // Static
        return [
            'type' => self::TYPE_STATIC,
            'name' => $segment,
        ];
    }

    /**
     * Verifica se um segmento é dinâmico (parâmetro, catch-all ou optional catch-all).
     *
     * @param string $segment Nome do segmento
     * @return bool True se o segmento é dinâmico
     */
    public static function isDynamic(string $segment): bool
    {
        $parsed = self::parse($segment);
        return in_array($parsed['type'], [
            self::TYPE_DYNAMIC,
            self::TYPE_CATCH_ALL,
            self::TYPE_OPTIONAL_CATCH_ALL,
        ], true);
    }

    /**
     * Verifica se um segmento é um route group.
     *
     * @param string $segment Nome do segmento
     * @return bool True se é route group
     */
    public static function isGroup(string $segment): bool
    {
        return self::parse($segment)['type'] === self::TYPE_GROUP;
    }

    /**
     * Converte um caminho de diretório em segmentos de URL.
     *
     * Remove route groups (que não afetam a URL) e normaliza
     * o caminho relativo ao diretório base.
     *
     * @param string $directoryPath Caminho completo do diretório
     * @param string $basePath Caminho base (app dir)
     * @return string[] Segmentos da URL (sem groups)
     */
    public static function directoryToUrlSegments(string $directoryPath, string $basePath): array
    {
        // Calcula o caminho relativo
        $relative = str_replace($basePath, '', $directoryPath);
        $relative = trim($relative, '/\\');

        if ($relative === '') {
            return [];
        }

        $parts = preg_split('/[\/\\\\]/', $relative);
        $segments = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            // Route groups são ignorados na URL
            if (self::isGroup($part)) {
                continue;
            }

            $segments[] = $part;
        }

        return $segments;
    }
}
