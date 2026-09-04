<?php

declare(strict_types=1);

namespace Arbor\Router\ErrorHandling;

use Arbor\Router\Http\HtmlResponse;
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\Response;
use Arbor\Router\Rendering\LayoutRenderer;
use Arbor\Router\Rendering\LayoutResolver;

/**
 * Gerenciador de erros globais e renderizador de páginas de erro.
 *
 * @package Arbor\Router\ErrorHandling
 */
class ErrorHandler
{
    public function __construct(
        private readonly ErrorPageResolver $resolver,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly LayoutResolver $layoutResolver,
        private readonly string $appDir,
    ) {}

    /**
     * Renderiza uma página 404.
     */
    public function handleNotFound(RequestInterface $request, ?string $directoryPath = null): Response
    {
        $dir = $directoryPath ?? $this->appDir;
        $file = $this->resolver->resolve($dir, $this->appDir, 'not-found.php');

        if ($file === null) {
            return new HtmlResponse('<h1>404 - Not Found</h1>', 404);
        }

        return $this->renderErrorPage($file, $dir, $request, 404);
    }

    /**
     * Renderiza uma página 500 ou de exceção genérica.
     */
    public function handleError(\Throwable $error, RequestInterface $request, ?string $directoryPath = null): Response
    {
        $dir = $directoryPath ?? $this->appDir;
        $file = $this->resolver->resolve($dir, $this->appDir, 'error.php');

        if ($file === null) {
            return new HtmlResponse('<h1>500 - Internal Server Error</h1><p>' . htmlspecialchars($error->getMessage()) . '</p>', 500);
        }

        return $this->renderErrorPage($file, $dir, $request, 500, ['error' => $error]);
    }
    
    private function renderErrorPage(string $file, string $directoryPath, RequestInterface $request, int $status, array $extraVars = []): Response
    {
        $vars = array_merge(['request' => $request], $extraVars);
        $content = $this->layoutRenderer->renderFile($file, $vars);
        
        $layoutResolution = $this->layoutResolver->resolve($directoryPath, $this->appDir);
        $chain = $this->layoutResolver->getRenderChain($layoutResolution['layouts'], $layoutResolution['layoutRoot']);
        
        if (!empty($chain)) {
             $content = $this->layoutRenderer->renderWithLayouts($content, $chain, $vars);
        }

        return new HtmlResponse($content, $status);
    }
}
