<?php

declare(strict_types=1);

namespace Arbor\Router\ErrorHandling;

use Arbor\Router\Routing\Route;

/**
 * Resolvedor de páginas de erro (error.php, not-found.php).
 *
 * Implementa bubbling up: busca do diretório da rota atual até a raiz.
 *
 * @package Arbor\Router\ErrorHandling
 */
class ErrorPageResolver
{
    /**
     * Resolve a página de erro mais próxima.
     *
     * @param string $directoryPath Diretório base
     * @param string $appDir Diretório raiz do app
     * @param string $filename Nome do arquivo ('error.php' ou 'not-found.php')
     * @return string|null Caminho do arquivo ou null
     */
    public function resolve(string $directoryPath, string $appDir, string $filename): ?string
    {
        $current = rtrim(str_replace('\\', '/', $directoryPath), '/');
        $appDir = rtrim(str_replace('\\', '/', $appDir), '/');

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
