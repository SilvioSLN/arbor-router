<?php

declare(strict_types=1);

namespace Arbor\Router\Http;

/**
 * Response HTTP para conteúdo texto plano.
 *
 * @package Arbor\Router\Http
 */
class TextResponse extends Response
{
    /**
     * @param string $text Conteúdo texto
     * @param int $statusCode Código HTTP (default: 200)
     * @param array<string, string> $headers Headers adicionais
     */
    public function __construct(
        string $text = '',
        int $statusCode = 200,
        array $headers = [],
    ) {
        $headers['content-type'] = 'text/plain; charset=utf-8';
        parent::__construct($text, $statusCode, $headers);
    }
}
