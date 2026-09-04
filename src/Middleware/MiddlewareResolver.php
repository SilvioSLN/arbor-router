<?php

declare(strict_types=1);

namespace Arbor\Router\Middleware;

/**
 * Resolvedor de middleware na árvore de diretórios.
 *
 * Carrega e resolve os middleware.php de cada nível da árvore.
 * Cada middleware.php deve retornar um array de middleware
 * (instâncias, callables ou nomes de classe).
 *
 * @package Arbor\Router\Middleware
 */
class MiddlewareResolver
{
    /**
     * Resolve e carrega middleware a partir de arquivos.
     *
     * @param string[] $middlewareFiles Caminhos dos middleware.php (raiz → folha)
     * @return array<int, MiddlewareInterface|callable|string> Middleware carregados
     */
    public function resolve(array $middlewareFiles): array
    {
        $middlewares = [];

        foreach ($middlewareFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $result = include $file;

            if (is_array($result)) {
                foreach ($result as $middleware) {
                    $middlewares[] = $middleware;
                }
            } elseif ($result instanceof MiddlewareInterface || is_callable($result)) {
                $middlewares[] = $result;
            }
        }

        return $middlewares;
    }

    /**
     * Cria uma MiddlewarePipeline a partir de arquivos de middleware.
     *
     * @param string[] $middlewareFiles Caminhos dos middleware.php
     * @return MiddlewarePipeline Pipeline configurada
     */
    public function createPipeline(array $middlewareFiles): MiddlewarePipeline
    {
        $pipeline = new MiddlewarePipeline();
        $middlewares = $this->resolve($middlewareFiles);
        $pipeline->pipeMany($middlewares);
        return $pipeline;
    }
}
