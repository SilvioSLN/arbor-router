<?php

declare(strict_types=1);

namespace Arbor\Router\Http;

/**
 * Response HTTP para conteúdo HTML.
 *
 * @package Arbor\Router\Http
 */
class HtmlResponse extends Response
{
    /**
     * @param string $html Conteúdo HTML
     * @param int $statusCode Código HTTP (default: 200)
     * @param array<string, string> $headers Headers adicionais
     */
    public function __construct(
        string $html = '',
        int $statusCode = 200,
        array $headers = [],
    ) {
        $headers['content-type'] = 'text/html; charset=utf-8';
        parent::__construct($html, $statusCode, $headers);
    }
}
