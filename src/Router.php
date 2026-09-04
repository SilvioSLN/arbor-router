<?php

declare(strict_types=1);

namespace Arbor\Router;

use Arbor\Router\Action\ActionGuard;
use Arbor\Router\Action\ActionHandler;
use Arbor\Router\Cache\CacheInterface;
use Arbor\Router\Cache\NullCache;
use Arbor\Router\ErrorHandling\ErrorHandler;
use Arbor\Router\ErrorHandling\ErrorPageResolver;
use Arbor\Router\Exception\ActionOriginMismatchException;
use Arbor\Router\Exception\CsrfTokenMismatchException;
use Arbor\Router\Exception\ForbiddenException;
use Arbor\Router\Exception\MethodNotAllowedException;
use Arbor\Router\Exception\RouteNotFoundException;
use Arbor\Router\Exception\ValidationException;
use Arbor\Router\Http\JsonResponse;
use Arbor\Router\Http\Request;
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\Response;
use Arbor\Router\Middleware\MiddlewareResolver;
use Arbor\Router\Rendering\ApiRenderer;
use Arbor\Router\Rendering\ContentNegotiator;
use Arbor\Router\Rendering\LayoutRenderer;
use Arbor\Router\Rendering\LayoutResolver;
use Arbor\Router\Rendering\PageRenderer;
use Arbor\Router\Routing\RouteMap;
use Arbor\Router\Routing\RouteMatcher;
use Arbor\Router\Routing\RouteScanner;
use Arbor\Router\Security\ApiGuard;
use Arbor\Router\Security\CsrfGuard;
use Arbor\Router\Security\SecurityManager;
use Arbor\Router\Validation\Validator;
use Arbor\Router\Sanitizer\Sanitizer;

/**
 * Classe principal do Arbor Router.
 *
 * Orquestra todo o ciclo de vida da requisição, desde o bootstrap,
 * parsing do Request, roteamento, middlewares, até a renderização.
 *
 * @package Arbor\Router
 */
class Router
{
    private readonly RouteMap $routeMap;
    private readonly RouteMatcher $matcher;
    private readonly SecurityManager $securityManager;
    private readonly CsrfGuard $csrfGuard;
    private readonly ApiGuard $apiGuard;
    private readonly ActionGuard $actionGuard;
    private readonly ActionHandler $actionHandler;
    private readonly ErrorHandler $errorHandler;
    private readonly MiddlewareResolver $middlewareResolver;
    
    private readonly PageRenderer $pageRenderer;
    private readonly ApiRenderer $apiRenderer;

    /**
     * Initializes the Arbor Router with the specified configuration.
     *
     * @param array{
     *     appDir: string,
     *     cacheInstance?: CacheInterface,
     *     security?: array{
     *         headers?: bool,
     *         csrf?: bool,
     *         apiHeader?: array{name?: string, value?: string},
     *         actionHeader?: array{name?: string, value?: string}
     *     }
     * } $config Array de configuração do Router
     */
    public function __construct(
        private readonly array $config,
    ) {
        $appDir = $this->config['appDir'] ?? throw new \InvalidArgumentException("Missing 'appDir' config");
        /** @var CacheInterface $cache */
        $cache = $this->config['cacheInstance'] ?? new NullCache();
        
        // --- 1. Inicializa dependências ---
        $this->securityManager = new SecurityManager(enabled: $this->config['security']['headers'] ?? true);
        $this->csrfGuard = new CsrfGuard();
        $this->apiGuard = new ApiGuard(
            $this->config['security']['apiHeader']['name'] ?? 'X-API-Request',
            $this->config['security']['apiHeader']['value'] ?? 'true'
        );
        $this->actionGuard = new ActionGuard(
            $this->config['security']['actionHeader']['name'] ?? 'X-Action-Request',
            $this->config['security']['actionHeader']['value'] ?? 'true',
            ($this->config['security']['csrf'] ?? true) ? $this->csrfGuard : null
        );

        $validator = new Validator();
        $sanitizer = new Sanitizer();
        $layoutRenderer = new LayoutRenderer();
        $layoutResolver = new LayoutResolver();
        
        $this->pageRenderer = new PageRenderer($layoutRenderer, $layoutResolver, $validator, $sanitizer);
        $this->apiRenderer = new ApiRenderer(new ContentNegotiator());
        $this->actionHandler = new ActionHandler($this->actionGuard, $layoutRenderer, $validator, $sanitizer);
        
        $this->errorHandler = new ErrorHandler(new ErrorPageResolver(), $layoutRenderer, $layoutResolver, $appDir);
        $this->middlewareResolver = new MiddlewareResolver();

        // --- 2. Resolve rotas (com cache) ---
        $cacheKey = 'arbor_routes_' . md5($appDir);
        $cachedMap = $cache->get($cacheKey);

        if ($cachedMap instanceof RouteMap) {
            $this->routeMap = $cachedMap;
        } else {
            $scanner = new RouteScanner($appDir);
            $this->routeMap = $scanner->scan();
            $cache->set($cacheKey, $this->routeMap);
        }

        $this->matcher = new RouteMatcher($this->routeMap);
    }

    /**
     * Processa a requisição atual.
     */
    public function dispatch(?RequestInterface $request = null): void
    {
        $request ??= Request::fromGlobals();
        
        // Aplica headers de segurança globais
        $this->securityManager->applyHeaders();

        try {
            $response = $this->handle($request);
        } catch (\Throwable $e) {
            $response = $this->handleException($e, $request);
        }

        $response->send();
    }

    /**
     * Resolve a requisição e retorna o Response.
     */
    public function handle(RequestInterface $request): Response
    {
        try {
            // Descobre qual rota casou, informando o método HTTP
            $matchResult = $this->matcher->matchAny($request->path(), $request->method());
            
            if ($matchResult === null) {
                throw new RouteNotFoundException();
            }

            /** @var \Arbor\Router\Routing\Route $route */
            $route = $matchResult['route'];
            $params = $matchResult['params'];

            // Se tem middleware, resolve a pipeline
            if (!empty($route->middlewareFiles)) {
                $pipeline = $this->middlewareResolver->createPipeline($route->middlewareFiles);
                if (!$pipeline->isEmpty()) {
                    return $pipeline->process($request, fn($req) => $this->executeRoute($route, $req, $params));
                }
            }

            return $this->executeRoute($route, $request, $params);

        } catch (RouteNotFoundException $e) {
            return $this->errorHandler->handleNotFound($request);
        } catch (\Throwable $e) {
            return $this->errorHandler->handleError($e, $request);
        }
    }

    /**
     * Executa a rota final (Page, Api, Action)
     */
    private function executeRoute(\Arbor\Router\Routing\Route $route, RequestInterface $request, array $params): Response
    {
        if ($route->isPage()) {
            return $this->pageRenderer->renderWithRoute($route, $request, $params);
        }

        if ($route->isApi()) {
            $this->apiGuard->validate($request);
            return $this->apiRenderer->render($route->filePath, $request, $params);
        }

        if ($route->isAction()) {
            // Action Handler cuida do Guard e tudo mais, recebendo o URL Pattern como escopo de segurança
            return $this->actionHandler->handle($route->filePath, $route->urlPattern, $request, $params);
        }

        throw new \LogicException('Unknown route type');
    }

    /**
     * Trata exceções e as converte em respostas apropriadas.
     */
    private function handleException(\Throwable $e, RequestInterface $request): Response
    {
        // Rota não encontrada
        if ($e instanceof RouteNotFoundException) {
            return $this->errorHandler->handleNotFound($request);
        }

        // Action Path mismatch, CSRF token mismatch, API header faltante (todas herdam de ForbiddenException)
        if ($e instanceof ForbiddenException) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        }
        
        // Método não permitido
        if ($e instanceof MethodNotAllowedException) {
            return new JsonResponse(['error' => $e->getMessage(), 'allowed' => $e->allowedMethods], 405);
        }
        
        // Erro de Validação (422)
        if ($e instanceof ValidationException) {
            if ($request->isAjax() || $request->header('X-Action-Request')) {
                 return new JsonResponse(['error' => 'Validation failed', 'errors' => $e->result?->errors() ?? []], 422);
            }
            // Em caso não AJAX, o comportamento padrão num framework seria redirecionar de volta.
            // Para simplicidade, vamos retornar o erro 500 renderizado pelo Handler se for page normal, mas actions resolvem via json acima.
        }

        // Erro 500 genérico
        return $this->errorHandler->handleError($e, $request);
    }
}
