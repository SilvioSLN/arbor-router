<?php

declare(strict_types=1);

namespace Arbor\Router\Routing;

use Arbor\Router\Exception\RouteNotFoundException;
use Arbor\Router\Exception\MethodNotAllowedException;

/**
 * Matcher de URL contra o RouteMap.
 *
 * Responsável por encontrar a rota correspondente a uma URL,
 * extraindo parâmetros dinâmicos no processo.
 *
 * O algoritmo de matching segue uma prioridade estrita:
 * 1. Correspondência estática exata (O(1) via hash map)
 * 2. Correspondência dinâmica `[param]` (segmento a segmento)
 * 3. Correspondência catch-all `[...slug]` (1+ segmentos)
 * 4. Correspondência optional catch-all `[[...slug]]` (0+ segmentos)
 *
 * Dentro de cada nível, rotas mais específicas (mais segmentos estáticos)
 * têm prioridade sobre rotas mais genéricas.
 *
 * @package Arbor\Router\Routing
 */
class RouteMatcher
{
    /**
     * @param RouteMap $routeMap Mapa de rotas para matching
     */
    public function __construct(
        private readonly RouteMap $routeMap,
    ) {}

    /**
     * Encontra a rota correspondente a uma URL e tipo.
     *
     * @param string $url URL normalizada (ex: /users/123)
     * @param RouteType $type Tipo de rota desejado (Page, Api, Action)
     * @return array{route: Route, params: array<string, mixed>} Rota e parâmetros
     * @throws RouteNotFoundException Se nenhuma rota corresponder
     */
    public function match(string $url, RouteType $type): array
    {
        $normalizedUrl = $this->normalizeUrl($url);

        // 1. Tenta match estático exato (mais rápido)
        $staticRoute = $this->routeMap->findStatic($normalizedUrl, $type);
        if ($staticRoute !== null) {
            return ['route' => $staticRoute, 'params' => []];
        }

        // 2. Tenta match dinâmico
        $urlSegments = $this->splitUrl($normalizedUrl);
        $dynamicRoutes = $this->routeMap->getDynamicRoutes($type);

        // Ordena rotas por especificidade (mais segmentos estáticos primeiro)
        usort($dynamicRoutes, fn(Route $a, Route $b) => $this->compareSpecificity($a, $b));

        foreach ($dynamicRoutes as $route) {
            $params = $this->tryMatch($urlSegments, $route);
            if ($params !== null) {
                return ['route' => $route, 'params' => $params];
            }
        }

        throw new RouteNotFoundException(
            "No route found for URL: {$normalizedUrl} (type: {$type->value})"
        );
    }

    public function matchAny(string $url, string $method = 'GET'): ?array
    {
        $normalizedUrl = $this->normalizeUrl($url);

        // Prioridade de tipos para desempate
        $priorities = match (strtoupper($method)) {
            'POST', 'PUT', 'DELETE', 'PATCH' => [RouteType::Action, RouteType::Api, RouteType::Page],
            default => [RouteType::Page, RouteType::Api, RouteType::Action],
        };

        // 1. Tenta match estático em todos os tipos primeiro, respeitando a prioridade
        foreach ($priorities as $type) {
            $staticRoute = $this->routeMap->findStatic($normalizedUrl, $type);
            if ($staticRoute !== null) {
                return ['route' => $staticRoute, 'params' => [], 'type' => $type];
            }
        }

        // 2. Tenta match dinâmico combinando todos os tipos
        $urlSegments = $this->splitUrl($normalizedUrl);
        $allDynamic = [];
        
        foreach ($priorities as $type) {
            foreach ($this->routeMap->getDynamicRoutes($type) as $route) {
                $allDynamic[] = ['route' => $route, 'type' => $type];
            }
        }

        // Ordena por especificidade (estáticos > dinâmicos)
        usort($allDynamic, function($a, $b) use ($priorities) {
            $cmp = $this->compareSpecificity($a['route'], $b['route']);
            if ($cmp !== 0) {
                return $cmp;
            }
            // Desempate por tipo
            $idxA = array_search($a['type'], $priorities, true);
            $idxB = array_search($b['type'], $priorities, true);
            return $idxA <=> $idxB;
        });

        foreach ($allDynamic as $item) {
            $params = $this->tryMatch($urlSegments, $item['route']);
            if ($params !== null) {
                return ['route' => $item['route'], 'params' => $params, 'type' => $item['type']];
            }
        }

        return null;
    }

    /**
     * Tenta fazer match de segmentos de URL contra uma rota.
     *
     * @param string[] $urlSegments Segmentos da URL
     * @param Route $route Rota candidata
     * @return array<string, mixed>|null Parâmetros extraídos ou null se não casou
     */
    private function tryMatch(array $urlSegments, Route $route): ?array
    {
        $routeSegments = $route->segments;
        $segmentDefs = array_values($route->segmentDefinitions);
        $params = [];

        $urlCount = count($urlSegments);
        $routeCount = count($routeSegments);

        $urlIndex = 0;
        $routeIndex = 0;

        while ($routeIndex < $routeCount) {
            $def = $segmentDefs[$routeIndex] ?? null;

            if ($def === null) {
                return null;
            }

            switch ($def['type']) {
                case SegmentParser::TYPE_STATIC:
                    // Segmento estático — deve casar exatamente
                    if ($urlIndex >= $urlCount || $urlSegments[$urlIndex] !== $def['name']) {
                        return null;
                    }
                    $urlIndex++;
                    break;

                case SegmentParser::TYPE_DYNAMIC:
                    // Parâmetro dinâmico — casa com qualquer segmento único
                    if ($urlIndex >= $urlCount) {
                        return null;
                    }
                    $params[$def['name']] = urldecode($urlSegments[$urlIndex]);
                    $urlIndex++;
                    break;

                case SegmentParser::TYPE_CATCH_ALL:
                    // Catch-all — casa com 1 ou mais segmentos restantes
                    if ($urlIndex >= $urlCount) {
                        return null; // Precisa de pelo menos 1 segmento
                    }
                    $remaining = array_slice($urlSegments, $urlIndex);
                    $params[$def['name']] = array_map('urldecode', $remaining);
                    $urlIndex = $urlCount; // Consumiu tudo
                    break;

                case SegmentParser::TYPE_OPTIONAL_CATCH_ALL:
                    // Optional catch-all — casa com 0 ou mais segmentos restantes
                    if ($urlIndex < $urlCount) {
                        $remaining = array_slice($urlSegments, $urlIndex);
                        $params[$def['name']] = array_map('urldecode', $remaining);
                    } else {
                        $params[$def['name']] = [];
                    }
                    $urlIndex = $urlCount; // Consumiu tudo
                    break;

                default:
                    return null;
            }

            $routeIndex++;
        }

        // Todos os segmentos da URL devem ter sido consumidos
        if ($urlIndex !== $urlCount) {
            return null;
        }

        return $params;
    }

    /**
     * Compara a especificidade de duas rotas para ordenação.
     *
     * Prioridade (maior = mais específica):
     * 1. Mais segmentos estáticos
     * 2. Menos segmentos dinâmicos
     * 3. Sem catch-all > com catch-all
     * 4. Catch-all obrigatório > catch-all opcional
     *
     * @return int Negativo se $a é mais específica, positivo se $b é mais específica
     */
    private function compareSpecificity(Route $a, Route $b): int
    {
        $aStatic = $this->countSegmentType($a, SegmentParser::TYPE_STATIC);
        $bStatic = $this->countSegmentType($b, SegmentParser::TYPE_STATIC);

        // Mais segmentos estáticos = mais específico
        if ($aStatic !== $bStatic) {
            return $bStatic - $aStatic; // Descending (mais estáticos primeiro)
        }

        // Menos dinâmicos = mais específico
        $aDynamic = $this->countSegmentType($a, SegmentParser::TYPE_DYNAMIC);
        $bDynamic = $this->countSegmentType($b, SegmentParser::TYPE_DYNAMIC);

        if ($aDynamic !== $bDynamic) {
            return $aDynamic - $bDynamic; // Ascending (menos dinâmicos primeiro)
        }

        // Sem catch-all > com catch-all
        $aCatchAll = $a->hasCatchAll() ? 1 : 0;
        $bCatchAll = $b->hasCatchAll() ? 1 : 0;

        return $aCatchAll - $bCatchAll;
    }

    /**
     * Conta segmentos de um tipo específico em uma rota.
     */
    private function countSegmentType(Route $route, string $type): int
    {
        $count = 0;
        foreach ($route->segmentDefinitions as $def) {
            if ($def['type'] === $type) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Normaliza uma URL removendo trailing slash e garantindo leading slash.
     */
    private function normalizeUrl(string $url): string
    {
        $trimmed = trim($url, '/');
        return $trimmed === '' ? '/' : '/' . $trimmed;
    }

    /**
     * Divide uma URL em segmentos.
     *
     * @return string[]
     */
    private function splitUrl(string $url): array
    {
        $trimmed = trim($url, '/');
        if ($trimmed === '') {
            return [];
        }
        return explode('/', $trimmed);
    }
}
