<?php

declare(strict_types=1);

namespace Arbor\Router\Routing;

/**
 * Mapa de rotas resolvido e serializável.
 *
 * Armazena todas as rotas descobertas pelo RouteScanner em uma estrutura
 * otimizada para lookup rápido. Esta classe é serializável para suporte
 * a cache de rotas.
 *
 * O mapa organiza rotas por tipo (page, api, action) e por padrão de URL
 * para matching eficiente.
 *
 * @package Arbor\Router\Routing
 */
class RouteMap
{
    /**
     * Todas as rotas indexadas por padrão de URL e tipo.
     *
     * @var array<string, array<string, Route>>
     */
    private array $routes = [];

    /**
     * Rotas estáticas para lookup O(1).
     *
     * @var array<string, array<string, Route>>
     */
    private array $staticRoutes = [];

    /**
     * Rotas dinâmicas ordenadas por prioridade.
     *
     * @var array<string, Route[]>
     */
    private array $dynamicRoutes = [];

    /**
     * Adiciona uma rota ao mapa.
     *
     * @param Route $route Rota a adicionar
     */
    public function addRoute(Route $route): void
    {
        $pattern = $route->urlPattern;
        $type = $route->type->value;

        $this->routes[$pattern][$type] = $route;

        if ($route->hasDynamicSegments()) {
            $this->dynamicRoutes[$type][] = $route;
        } else {
            $this->staticRoutes[$pattern][$type] = $route;
        }
    }

    /**
     * Busca uma rota estática exata por URL e tipo.
     *
     * @param string $url URL normalizada (ex: /users)
     * @param RouteType $type Tipo da rota
     * @return Route|null Rota encontrada ou null
     */
    public function findStatic(string $url, RouteType $type): ?Route
    {
        return $this->staticRoutes[$url][$type->value] ?? null;
    }

    /**
     * Retorna todas as rotas dinâmicas de um tipo.
     *
     * @param RouteType $type Tipo da rota
     * @return Route[] Rotas dinâmicas
     */
    public function getDynamicRoutes(RouteType $type): array
    {
        return $this->dynamicRoutes[$type->value] ?? [];
    }

    /**
     * Retorna todas as rotas do mapa.
     *
     * @return Route[] Todas as rotas
     */
    public function all(): array
    {
        $all = [];
        foreach ($this->routes as $patterns) {
            foreach ($patterns as $route) {
                $all[] = $route;
            }
        }
        return $all;
    }

    /**
     * Retorna todas as rotas de um tipo específico.
     *
     * @param RouteType $type Tipo da rota
     * @return Route[] Rotas do tipo especificado
     */
    public function allOfType(RouteType $type): array
    {
        $result = [];
        foreach ($this->routes as $patterns) {
            if (isset($patterns[$type->value])) {
                $result[] = $patterns[$type->value];
            }
        }
        return $result;
    }

    /**
     * Verifica se o mapa está vazio.
     */
    public function isEmpty(): bool
    {
        return empty($this->routes);
    }

    /**
     * Retorna o número total de rotas.
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->routes as $patterns) {
            $count += count($patterns);
        }
        return $count;
    }

    /**
     * Serializa o mapa para armazenamento em cache.
     *
     * @return string Representação serializada
     */
    public function serialize(): string
    {
        return serialize($this);
    }

    /**
     * Deserializa um mapa a partir do cache.
     *
     * @param string $data Dados serializados
     * @return static Mapa reconstruído
     */
    public static function deserialize(string $data): static
    {
        $map = unserialize($data);
        if (!$map instanceof static) {
            throw new \RuntimeException('Invalid serialized RouteMap data');
        }
        return $map;
    }
}
