<?php

declare(strict_types=1);

namespace Arbor\Router\Rendering;

use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\Response;

/**
 * Interface para motores de renderização.
 *
 * @package Arbor\Router\Rendering
 */
interface RendererInterface
{
    /**
     * Renderiza uma rota e retorna um Response.
     *
     * @param string $filePath Caminho do arquivo handler
     * @param RequestInterface $request Requisição HTTP
     * @param array<string, mixed> $params Parâmetros da rota
     * @param array<string, mixed> $extraVars Variáveis extras a injetar
     * @return Response Resposta renderizada
     */
    public function render(
        string $filePath,
        RequestInterface $request,
        array $params = [],
        array $extraVars = [],
    ): Response;
}
