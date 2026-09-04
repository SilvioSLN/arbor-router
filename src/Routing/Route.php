<?php

declare(strict_types=1);

namespace Arbor\Router\Routing;

/**
 * Value Object representando uma rota resolvida.
 *
 * Contém todas as informações necessárias para processar uma requisição:
 * - Tipo da rota (Page, Api, Action)
 * - Caminho do arquivo handler
 * - Segmentos URL
 * - Parâmetros dinâmicos capturados
 * - Caminhos dos layouts na árvore
 * - Caminhos dos middlewares na árvore
 * - Caminhos de arquivos especiais (error, not-found, loading)
 *
 * @package Arbor\Router\Routing
 */
class Route
{
    /**
     * @param RouteType $type Tipo da rota
     * @param string $filePath Caminho absoluto do arquivo handler (page.php, route.php, action.php)
     * @param string $directoryPath Caminho absoluto do diretório da rota
     * @param string $urlPattern Padrão URL (ex: /users/[id])
     * @param string[] $segments Segmentos de URL parseados
     * @param array<string, array{type: string, name: string}> $segmentDefinitions Definições dos segmentos
     * @param string[] $layoutFiles Caminhos dos layout.php (da raiz à folha)
     * @param string|null $layoutRootFile Caminho do layoutroot.php (se existir)
     * @param string[] $middlewareFiles Caminhos dos middleware.php (da raiz à folha)
     * @param string|null $errorFile Caminho do error.php mais próximo
     * @param string|null $notFoundFile Caminho do not-found.php mais próximo
     * @param string|null $loadingFile Caminho do loading.php mais próximo
     */
    public function __construct(
        public readonly RouteType $type,
        public readonly string $filePath,
        public readonly string $directoryPath,
        public readonly string $urlPattern,
        public readonly array $segments = [],
        public readonly array $segmentDefinitions = [],
        public readonly array $layoutFiles = [],
        public readonly ?string $layoutRootFile = null,
        public readonly array $middlewareFiles = [],
        public readonly ?string $errorFile = null,
        public readonly ?string $notFoundFile = null,
        public readonly ?string $loadingFile = null,
    ) {}

    /**
     * Verifica se esta rota é do tipo Page.
     */
    public function isPage(): bool
    {
        return $this->type === RouteType::Page;
    }

    /**
     * Verifica se esta rota é do tipo Api.
     */
    public function isApi(): bool
    {
        return $this->type === RouteType::Api;
    }

    /**
     * Verifica se esta rota é do tipo Action.
     */
    public function isAction(): bool
    {
        return $this->type === RouteType::Action;
    }

    /**
     * Verifica se esta rota tem segmentos dinâmicos.
     */
    public function hasDynamicSegments(): bool
    {
        foreach ($this->segmentDefinitions as $def) {
            if ($def['type'] !== SegmentParser::TYPE_STATIC) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se esta rota tem catch-all ou optional catch-all.
     */
    public function hasCatchAll(): bool
    {
        foreach ($this->segmentDefinitions as $def) {
            if (in_array($def['type'], [SegmentParser::TYPE_CATCH_ALL, SegmentParser::TYPE_OPTIONAL_CATCH_ALL], true)) {
                return true;
            }
        }
        return false;
    }
}
