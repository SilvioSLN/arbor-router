<?php

declare(strict_types=1);

namespace Arbor\Router\Middleware;

use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\Response;

/**
 * Pipeline de execução de middleware.
 *
 * Executa uma cadeia de middleware em sequência usando o padrão
 * "Russian doll" — cada middleware envolve o próximo, permitindo
 * processamento antes e depois da requisição.
 *
 * Suporta middleware como:
 * - Instâncias de MiddlewareInterface
 * - Callables (closures, funções)
 * - Nomes de classes (instanciados automaticamente)
 *
 * @package Arbor\Router\Middleware
 */
class MiddlewarePipeline
{
    /**
     * @var array<int, MiddlewareInterface|callable> Middleware na pipeline
     */
    private array $middlewares = [];

    /**
     * Adiciona um middleware à pipeline.
     *
     * @param MiddlewareInterface|callable|string $middleware Middleware
     * @return $this Fluent interface
     */
    public function pipe(MiddlewareInterface|callable|string $middleware): static
    {
        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (!$instance instanceof MiddlewareInterface) {
                throw new \InvalidArgumentException(
                    "Class {$middleware} must implement MiddlewareInterface"
                );
            }
            $this->middlewares[] = $instance;
        } else {
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Adiciona múltiplos middlewares à pipeline.
     *
     * @param array<int, MiddlewareInterface|callable|string> $middlewares
     * @return $this Fluent interface
     */
    public function pipeMany(array $middlewares): static
    {
        foreach ($middlewares as $middleware) {
            $this->pipe($middleware);
        }
        return $this;
    }

    /**
     * Executa a pipeline de middleware.
     *
     * @param RequestInterface $request Requisição HTTP
     * @param callable(RequestInterface): Response $finalHandler Handler final (rota)
     * @return Response Resposta HTTP
     */
    public function process(RequestInterface $request, callable $finalHandler): Response
    {
        $pipeline = $this->buildPipeline($finalHandler);
        return $pipeline($request);
    }

    /**
     * Constrói a pipeline encadeada de trás para frente.
     *
     * @param callable(RequestInterface): Response $finalHandler
     * @return callable(RequestInterface): Response
     */
    private function buildPipeline(callable $finalHandler): callable
    {
        $next = $finalHandler;

        // Constrói de trás para frente (último middleware envolve o handler final)
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = $this->wrapMiddleware($middleware, $next);
        }

        return $next;
    }

    /**
     * Envolve um middleware com o próximo handler.
     *
     * @param MiddlewareInterface|callable $middleware
     * @param callable(RequestInterface): Response $next
     * @return callable(RequestInterface): Response
     */
    private function wrapMiddleware(
        MiddlewareInterface|callable $middleware,
        callable $next,
    ): callable {
        if ($middleware instanceof MiddlewareInterface) {
            return fn(RequestInterface $request): Response => $middleware->handle($request, $next);
        }

        // Callable: function($request, $next) { ... }
        return fn(RequestInterface $request): Response => $middleware($request, $next);
    }

    /**
     * Verifica se a pipeline está vazia.
     */
    public function isEmpty(): bool
    {
        return empty($this->middlewares);
    }

    /**
     * Retorna o número de middlewares na pipeline.
     */
    public function count(): int
    {
        return count($this->middlewares);
    }
}
