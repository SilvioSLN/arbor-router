<?php

declare(strict_types=1);

namespace Arbor\Router\Rendering;

use Arbor\Router\Http\HtmlResponse;
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\Response;
use Arbor\Router\Routing\Route;
use Arbor\Router\Validation\Validator;
use Arbor\Router\Sanitizer\Sanitizer;

/**
 * Renderizador de páginas (page.php) com sistema de layouts.
 *
 * Orquestra a renderização completa de uma página:
 * 1. Renderiza o page.php com output buffering
 * 2. Resolve e aplica a cadeia de layouts (folha → raiz)
 * 3. Retorna um HtmlResponse com o conteúdo final
 *
 * Variáveis automaticamente disponíveis nos arquivos:
 * - $params: Parâmetros dinâmicos da rota
 * - $request: Objeto Request
 * - $query: Parâmetros GET
 * - $body: Parâmetros POST
 * - $validator: Instância do Validator
 * - $sanitizer: Instância do Sanitizer
 * - $children: Conteúdo do nível inferior (apenas em layouts)
 *
 * @package Arbor\Router\Rendering
 */
class PageRenderer implements RendererInterface
{
    public function __construct(
        private readonly LayoutRenderer $layoutRenderer,
        private readonly LayoutResolver $layoutResolver,
        private readonly Validator $validator,
        private readonly Sanitizer $sanitizer,
    ) {}

    /**
     * {@inheritdoc}
     *
     * Renderiza um page.php com toda a cadeia de layouts.
     */
    public function render(
        string $filePath,
        RequestInterface $request,
        array $params = [],
        array $extraVars = [],
    ): Response {
        // Monta variáveis a injetar
        $variables = $this->buildVariables($request, $params, $extraVars);

        // Renderiza o page.php
        $pageContent = $this->layoutRenderer->renderFile($filePath, $variables);

        return new HtmlResponse($pageContent);
    }

    /**
     * Renderiza um page.php com a cadeia de layouts de uma Route.
     *
     * @param Route $route Rota resolvida com informações de layout
     * @param RequestInterface $request Requisição HTTP
     * @param array<string, mixed> $params Parâmetros da rota
     * @param array<string, mixed> $extraVars Variáveis extras
     * @return Response Resposta HTML completa
     */
    public function renderWithRoute(
        Route $route,
        RequestInterface $request,
        array $params = [],
        array $extraVars = [],
    ): Response {
        $variables = $this->buildVariables($request, $params, $extraVars);

        // Renderiza o page.php primeiro
        $pageContent = $this->layoutRenderer->renderFile($route->filePath, $variables);

        // Monta a cadeia de layouts
        $layoutChain = $this->layoutResolver->getRenderChain(
            $route->layoutFiles,
            $route->layoutRootFile,
        );

        // Aplica layouts recursivamente
        if (!empty($layoutChain)) {
            $pageContent = $this->layoutRenderer->renderWithLayouts(
                $pageContent,
                $layoutChain,
                $variables,
            );
        }

        return new HtmlResponse($pageContent);
    }

    /**
     * Constrói o array de variáveis a injetar nos arquivos PHP.
     *
     * @param RequestInterface $request Requisição HTTP
     * @param array<string, mixed> $params Parâmetros da rota
     * @param array<string, mixed> $extraVars Variáveis extras
     * @return array<string, mixed> Variáveis completas
     */
    private function buildVariables(
        RequestInterface $request,
        array $params,
        array $extraVars,
    ): array {
        return array_merge([
            'params' => $params,
            'request' => $request,
            'query' => $request->query(),
            'body' => $request->body(),
            'validator' => $this->validator,
            'sanitizer' => $this->sanitizer,
        ], $extraVars);
    }
}
