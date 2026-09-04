<?php

declare(strict_types=1);

namespace Arbor\Router\Http;

/**
 * Response HTTP para respostas JSON.
 *
 * Serializa automaticamente dados para JSON e define
 * o Content-Type adequado.
 *
 * @package Arbor\Router\Http
 */
class JsonResponse extends Response
{
    /**
     * @param mixed $data Dados a serializar como JSON
     * @param int $statusCode Código HTTP (default: 200)
     * @param array<string, string> $headers Headers adicionais
     * @param int $encodingOptions Opções do json_encode
     */
    public function __construct(
        mixed $data = null,
        int $statusCode = 200,
        array $headers = [],
        int $encodingOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ) {
        $body = json_encode($data, $encodingOptions);

        $headers['content-type'] = 'application/json; charset=utf-8';

        parent::__construct($body, $statusCode, $headers);
    }
}
