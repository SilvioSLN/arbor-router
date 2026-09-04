<?php

declare(strict_types=1);

namespace Arbor\Router\Middleware;

use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\Response;

/**
 * Interface para middleware.
 *
 * Middleware segue o padrão pipeline: recebe a requisição e um
 * callable $next para delegar ao próximo middleware na cadeia.
 * Pode interromper a cadeia retornando um Response diretamente.
 *
 * @package Arbor\Router\Middleware
 */
interface MiddlewareInterface
{
    /**
     * Processa a requisição.
     *
     * @param RequestInterface $request Requisição HTTP
     * @param callable(RequestInterface): Response $next Próximo middleware
     * @return Response Resposta HTTP
     */
    public function handle(RequestInterface $request, callable $next): Response;
}
