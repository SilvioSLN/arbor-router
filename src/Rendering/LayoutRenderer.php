<?php

declare(strict_types=1);

namespace Arbor\Router\Rendering;

use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Validation\Validator;
use Arbor\Router\Sanitizer\Sanitizer;

/**
 * Renderiza layouts recursivamente com injeção de $children.
 *
 * Implementa o padrão de renderização em cascata: o conteúdo do
 * page.php é renderizado primeiro, depois envolvido pelo layout
 * mais interno, que por sua vez é envolvido pelo layout pai,
 * até chegar ao layoutroot.
 *
 * Em cada nível, o conteúdo do nível anterior é injetado como
 * a variável $children, disponível para o layout via extract().
 *
 * @package Arbor\Router\Rendering
 */
class LayoutRenderer
{
    /**
     * Renderiza um arquivo PHP usando output buffering.
     *
     * O arquivo é incluído em escopo isolado com as variáveis
     * fornecidas injetadas via extract(). Tanto output (echo/HTML)
     * quanto return values são capturados.
     *
     * @param string $filePath Caminho do arquivo PHP
     * @param array<string, mixed> $variables Variáveis a injetar
     * @return string Conteúdo renderizado
     */
    public function renderFile(string $filePath, array $variables = []): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        // Usa closure para isolar o escopo das variáveis
        $render = static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            $__result = include $__file;
            $output = ob_get_clean();

            // Permite padrão Moderno: O arquivo retornou uma função de renderização
            if (is_callable($__result)) {
                // Invoca passando a Requisição e os Parâmetros que vieram no extract
                $__result = call_user_func($__result, $__vars['request'] ?? null, $__vars['params'] ?? []);
            }

            // Se o arquivo ou a closure retornou uma string, usa-a
            if (is_string($__result)) {
                return $__result;
            }

            return $output ?: '';
        };

        return $render($filePath, $variables);
    }

    /**
     * Renderiza uma cadeia completa de layouts com um conteúdo base.
     *
     * Algoritmo de renderização recursiva (inside-out):
     * 1. Começa com o conteúdo base (page.php renderizado)
     * 2. Para cada layout na cadeia (da folha para a raiz):
     *    a. Injeta o conteúdo atual como $children
     *    b. Renderiza o layout
     *    c. O resultado torna-se o novo conteúdo
     * 3. O resultado final é o conteúdo completamente envolvido
     *
     * A cadeia deve ser ordenada da raiz à folha (mesmo sentido
     * do resultado do LayoutResolver). O método inverte internamente
     * para processar de dentro para fora.
     *
     * @param string $baseContent Conteúdo do page.php já renderizado
     * @param string[] $layoutChain Cadeia de layouts (raiz → folha)
     * @param array<string, mixed> $variables Variáveis a injetar em cada layout
     * @return string Conteúdo final renderizado com todos os layouts
     */
    public function renderWithLayouts(
        string $baseContent,
        array $layoutChain,
        array $variables = [],
    ): string {
        if (empty($layoutChain)) {
            return $baseContent;
        }

        $content = $baseContent;

        // Processa de dentro para fora (folha → raiz)
        // A cadeia vem raiz → folha, então invertemos
        $reversedChain = array_reverse($layoutChain);

        foreach ($reversedChain as $layoutFile) {
            // Injeta o conteúdo atual como $children (para backwards compatibility)
            $layoutVars = array_merge($variables, ['children' => $content]);
            
            $renderedLayout = $this->renderFile($layoutFile, $layoutVars);
            
            // Substituição mágica e elegante do slot prometida pela arquitetura
            if (str_contains($renderedLayout, '{{content}}')) {
                $content = str_replace('{{content}}', $content, $renderedLayout);
            } else {
                $content = $renderedLayout;
            }
        }

        return $content;
    }
}
