<?php

declare(strict_types=1);

namespace Arbor\Router\Http;

/**
 * Response HTTP para redirecionamentos.
 *
 * Define o header Location e status code de redirect (302 por padrão).
 *
 * @package Arbor\Router\Http
 */
final class RedirectResponse extends Response
{
    /**
     * @param string $url Target redirect destination URL
     * @param int $statusCode HTTP redirect status code (default: 302 Found)
     * @param array<string, string> $headers Additional HTTP headers
     */
    public function __construct(
        string $url,
        int $statusCode = 302,
        array $headers = [],
    ) {
        $headers['location'] = $url;
        parent::__construct('', $statusCode, $headers);
    }

    /**
     * Creates a permanent redirect (301 Moved Permanently).
     */
    public static function permanent(string $url): self
    {
        return new self($url, 301);
    }

    /**
     * Creates a temporary redirect (302 Found).
     */
    public static function temporary(string $url): self
    {
        return new self($url, 302);
    }
}
