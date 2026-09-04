<?php

declare(strict_types=1);

namespace Arbor\Router\Routing;

/**
 * Scanner recursivo do filesystem para descoberta automática de rotas.
 *
 * Percorre a árvore de diretórios do aplicativo (appDir) e detecta
 * automaticamente arquivos de rota, layouts, middleware e arquivos
 * especiais (error, not-found, loading).
 *
 * O scanner reconhece os seguintes arquivos:
 * - `page.php` → Rota de página (RouteType::Page)
 * - `route.php` → Rota de API (RouteType::Api)
 * - `action.php` → Rota de ação (RouteType::Action)
 * - `layout.php` → Layout parcial (herança em cascata)
 * - `layoutroot.php` → Layout raiz/âncora (teto da árvore de layouts)
 * - `middleware.php` → Middleware por escopo
 * - `error.php` → Página de erro por escopo
 * - `not-found.php` → Página 404 por escopo
 * - `loading.php` → Estado de carregamento por escopo
 *
 * @package Arbor\Router\Routing
 */
class RouteScanner
{
    /**
     * Nomes de arquivos reconhecidos como handlers de rota.
     */
    private const ROUTE_FILES = [
        'page.php' => RouteType::Page,
        'route.php' => RouteType::Api,
        'action.php' => RouteType::Action,
    ];

    /**
     * Nomes de arquivos especiais reconhecidos.
     */
    public const SPECIAL_FILES = [
        'layout.php',
        'layoutroot.php',
        'middleware.php',
        'error.php',
        'not-found.php',
        'loading.php',
    ];

    /**
     * @param string $appDir Caminho absoluto do diretório raiz do app
     */
    public function __construct(
        private readonly string $appDir,
    ) {
        if (!is_dir($this->appDir)) {
            throw new \InvalidArgumentException(
                "App directory does not exist: {$this->appDir}"
            );
        }
    }

    /**
     * Escaneia o diretório do app e gera um RouteMap completo.
     *
     * O algoritmo:
     * 1. Percorre recursivamente todos os diretórios
     * 2. Para cada diretório com arquivo de rota (page/route/action.php):
     *    a. Calcula o padrão de URL baseado no caminho (excluindo groups)
     *    b. Resolve layouts na árvore (bottom-up até layoutroot ou raiz)
     *    c. Resolve middlewares na árvore (top-down da raiz à folha)
     *    d. Resolve arquivos de erro mais próximos (bubbling up)
     *    e. Cria um objeto Route e adiciona ao RouteMap
     *
     * @return RouteMap Mapa de rotas completo
     */
    public function scan(): RouteMap
    {
        $map = new RouteMap();
        $this->scanDirectory($this->appDir, $map);
        return $map;
    }

    /**
     * Escaneia recursivamente um diretório.
     *
     * @param string $directory Caminho do diretório atual
     * @param RouteMap $map Mapa de rotas sendo construído
     */
    private function scanDirectory(string $directory, RouteMap $map): void
    {
        $normalizedDir = rtrim(str_replace('\\', '/', $directory), '/');
        $normalizedAppDir = rtrim(str_replace('\\', '/', $this->appDir), '/');

        // Verifica arquivos de rota neste diretório
        foreach (self::ROUTE_FILES as $filename => $routeType) {
            $filePath = $normalizedDir . '/' . $filename;

            if (file_exists($filePath)) {
                $route = $this->buildRoute(
                    $routeType,
                    $filePath,
                    $normalizedDir,
                    $normalizedAppDir,
                );
                $map->addRoute($route);
            }
        }

        // Escaneia subdiretórios recursivamente
        $entries = scandir($directory);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $normalizedDir . '/' . $entry;
            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $map);
            }
        }
    }

    /**
     * Constrói um objeto Route completo para um arquivo handler encontrado.
     *
     * @param RouteType $type Tipo da rota
     * @param string $filePath Caminho do arquivo handler
     * @param string $directoryPath Caminho do diretório
     * @param string $appDir Caminho raiz do app normalizado
     * @return Route Rota construída
     */
    private function buildRoute(
        RouteType $type,
        string $filePath,
        string $directoryPath,
        string $appDir,
    ): Route {
        $segments = SegmentParser::directoryToUrlSegments($directoryPath, $appDir);
        $segmentDefinitions = $this->buildSegmentDefinitions($directoryPath, $appDir);
        $urlPattern = $this->buildUrlPattern($segments);

        // Resolve layouts, middleware e arquivos especiais
        $layoutResolution = $this->resolveLayouts($directoryPath, $appDir);
        $middlewareFiles = $this->resolveMiddleware($directoryPath, $appDir);
        $errorFile = $this->resolveSpecialFile('error.php', $directoryPath, $appDir);
        $notFoundFile = $this->resolveSpecialFile('not-found.php', $directoryPath, $appDir);
        $loadingFile = $this->resolveSpecialFile('loading.php', $directoryPath, $appDir);

        return new Route(
            type: $type,
            filePath: $filePath,
            directoryPath: $directoryPath,
            urlPattern: $urlPattern,
            segments: $segments,
            segmentDefinitions: $segmentDefinitions,
            layoutFiles: $layoutResolution['layouts'],
            layoutRootFile: $layoutResolution['layoutRoot'],
            middlewareFiles: $middlewareFiles,
            errorFile: $errorFile,
            notFoundFile: $notFoundFile,
            loadingFile: $loadingFile,
        );
    }

    /**
     * Constrói as definições de segmento para cada parte do caminho.
     *
     * Inclui todos os segmentos do caminho relativo (incluindo groups,
     * para que o matcher saiba ignorá-los).
     *
     * @param string $directoryPath Caminho do diretório
     * @param string $appDir Caminho raiz
     * @return array<string, array{type: string, name: string}>
     */
    private function buildSegmentDefinitions(string $directoryPath, string $appDir): array
    {
        $relative = trim(str_replace($appDir, '', $directoryPath), '/');
        if ($relative === '') {
            return [];
        }

        $parts = explode('/', $relative);
        $definitions = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            // Route groups são ignorados na URL — não geram segmento para matching
            if (SegmentParser::isGroup($part)) {
                continue;
            }

            $definitions[$part] = SegmentParser::parse($part);
        }

        return $definitions;
    }

    /**
     * Constrói o padrão de URL a partir dos segmentos.
     *
     * @param string[] $segments Segmentos de URL
     * @return string Padrão URL (ex: /users/[id])
     */
    private function buildUrlPattern(array $segments): string
    {
        if (empty($segments)) {
            return '/';
        }
        return '/' . implode('/', $segments);
    }

    /**
     * Resolve a árvore de layouts para um diretório.
     *
     * Algoritmo bottom-up:
     * 1. Começa no diretório da rota
     * 2. Verifica se existe layout.php — se sim, adiciona à lista
     * 3. Verifica se existe layoutroot.php — se sim, define como âncora e PARA
     * 4. Sobe para o diretório pai
     * 5. Repete até encontrar layoutroot.php ou chegar à raiz (appDir)
     *
     * O resultado é ordenado da raiz para a folha (layoutroot primeiro,
     * layout mais interno por último).
     *
     * @param string $directoryPath Caminho do diretório da rota
     * @param string $appDir Caminho raiz do app
     * @return array{layouts: string[], layoutRoot: string|null}
     */
    private function resolveLayouts(string $directoryPath, string $appDir): array
    {
        $layouts = [];
        $layoutRoot = null;
        $current = $directoryPath;

        while (true) {
            // Verifica layoutroot.php (âncora/teto)
            $layoutRootFile = $current . '/layoutroot.php';
            if (file_exists($layoutRootFile)) {
                $layoutRoot = $layoutRootFile;
                // layoutroot é o teto — não sobe mais
                break;
            }

            // Verifica layout.php
            $layoutFile = $current . '/layout.php';
            if (file_exists($layoutFile)) {
                $layouts[] = $layoutFile;
            }

            // Chegou na raiz do app — para
            if ($current === $appDir) {
                break;
            }

            // Sobe para o diretório pai
            $parent = dirname($current);

            // Proteção contra loop infinito
            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        // Inverte para ficar da raiz à folha
        $layouts = array_reverse($layouts);

        return [
            'layouts' => $layouts,
            'layoutRoot' => $layoutRoot,
        ];
    }

    /**
     * Resolve os middlewares da árvore para um diretório.
     *
     * Algoritmo top-down: coleta middleware.php da raiz até a folha.
     *
     * @param string $directoryPath Caminho do diretório
     * @param string $appDir Caminho raiz do app
     * @return string[] Caminhos dos middleware.php (raiz → folha)
     */
    private function resolveMiddleware(string $directoryPath, string $appDir): array
    {
        $middlewares = [];
        $current = $directoryPath;

        while (true) {
            $file = $current . '/middleware.php';
            if (file_exists($file)) {
                $middlewares[] = $file;
            }

            if ($current === $appDir) {
                break;
            }

            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        // Inverte para ficar da raiz à folha (top-down)
        return array_reverse($middlewares);
    }

    /**
     * Resolve um arquivo especial (error.php, not-found.php, loading.php) via bubbling up.
     *
     * Busca do diretório mais específico (folha) subindo até a raiz.
     * Retorna o primeiro encontrado.
     *
     * @param string $filename Nome do arquivo a buscar
     * @param string $directoryPath Caminho do diretório
     * @param string $appDir Caminho raiz do app
     * @return string|null Caminho do arquivo ou null se não encontrado
     */
    private function resolveSpecialFile(string $filename, string $directoryPath, string $appDir): ?string
    {
        $current = $directoryPath;

        while (true) {
            $file = $current . '/' . $filename;
            if (file_exists($file)) {
                return $file;
            }

            if ($current === $appDir) {
                break;
            }

            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        return null;
    }
}
