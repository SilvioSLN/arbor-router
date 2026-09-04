<?php

declare(strict_types=1);

namespace Arbor\Router\Http;

/**
 * Response HTTP base.
 *
 * Encapsula status code, headers e corpo da resposta. Serve como
 * classe base para respostas tipadas (JSON, HTML, XML, etc).
 *
 * @package Arbor\Router\Http
 */
class Response
{
    /**
     * Headers HTTP da resposta.
     *
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * @param string $body Corpo da resposta
     * @param int $statusCode Código HTTP (default: 200)
     * @param array<string, string> $headers Headers adicionais
     */
    public function __construct(
        protected string $body = '',
        protected int $statusCode = 200,
        array $headers = [],
    ) {
        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = $value;
        }
    }

    /**
     * Retorna o corpo da resposta.
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Retorna o status code HTTP.
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Define o status code HTTP.
     *
     * @return static Nova instância clonada
     */
    public function withStatus(int $statusCode): static
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }

    /**
     * Retorna todos os headers.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Retorna o valor de um header específico.
     */
    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Adiciona/substitui um header.
     *
     * @return static Nova instância clonada
     */
    public function withHeader(string $name, string $value): static
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = $value;
        return $clone;
    }

    /**
     * Define o corpo da resposta.
     *
     * @return static Nova instância clonada
     */
    public function withBody(string $body): static
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /**
     * Envia a resposta HTTP para o cliente.
     *
     * Define o status code, envia todos os headers e emite o corpo.
     * Este método deve ser chamado apenas uma vez.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                // Normaliza nome do header para Title-Case
                $headerName = implode('-', array_map('ucfirst', explode('-', $name)));
                header("{$headerName}: {$value}");
            }
        }

        echo $this->body;
    }
}
