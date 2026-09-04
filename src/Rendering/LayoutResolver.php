<?php

declare(strict_types=1);

namespace Arbor\Router\Rendering;

/**
 * Resolvedor de layouts na árvore de diretórios.
 *
 * Implementa o algoritmo de resolução bottom-up para encontrar
 * todos os layouts aplicáveis a uma rota, desde o layoutroot (âncora)
 * até o layout mais interno (folha).
 *
 * Conceitos:
 * - **layoutroot.php**: Âncora/teto da árvore. Quando encontrado, a busca
 *   para. Normalmente contém o shell HTML (<html>, <head>, <body>).
 * - **layout.php**: Layout parcial que recebe $children do nível inferior.
 *   Layouts cascateiam da raiz à folha.
 *
 * A resolução já é feita pelo RouteScanner. Esta classe fornece
 * funcionalidade adicional para manipulação dos layouts resolvidos.
 *
 * @package Arbor\Router\Rendering
 */
class LayoutResolver
{
    /**
     * Resolve a cadeia completa de layouts para um diretório.
     *
     * Retorna os arquivos de layout ordenados da raiz à folha,
     * com o layoutroot (se existir) como primeiro elemento separado.
     *
     * @param string $directoryPath Caminho do diretório da rota
     * @param string $appDir Caminho raiz do app
     * @return array{layouts: string[], layoutRoot: string|null}
     */
    public function resolve(string $directoryPath, string $appDir): array
    {
        $layouts = [];
        $layoutRoot = null;
        $current = rtrim(str_replace('\\', '/', $directoryPath), '/');
        $appDir = rtrim(str_replace('\\', '/', $appDir), '/');

        while (true) {
            // Verifica layoutroot.php (âncora/teto)
            $layoutRootFile = $current . '/layoutroot.php';
            if (file_exists($layoutRootFile)) {
                $layoutRoot = $layoutRootFile;
                break;
            }

            // Verifica layout.php
            $layoutFile = $current . '/layout.php';
            if (file_exists($layoutFile)) {
                $layouts[] = $layoutFile;
            }

            // Chegou na raiz do app
            if ($current === $appDir) {
                break;
            }

            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        // Inverte para ficar da raiz à folha
        return [
            'layouts' => array_reverse($layouts),
            'layoutRoot' => $layoutRoot,
        ];
    }

    /**
     * Retorna a cadeia completa de renderização (layoutroot + layouts).
     *
     * O resultado é ordenado para renderização de fora para dentro:
     * layoutroot → layout(raiz) → ... → layout(folha)
     *
     * @param string[] $layoutFiles Layouts da raiz à folha
     * @param string|null $layoutRootFile Layoutroot se existir
     * @return string[] Cadeia completa para renderização
     */
    public function getRenderChain(array $layoutFiles, ?string $layoutRootFile): array
    {
        $chain = [];

        if ($layoutRootFile !== null) {
            $chain[] = $layoutRootFile;
        }

        foreach ($layoutFiles as $layout) {
            $chain[] = $layout;
        }

        return $chain;
    }
}
