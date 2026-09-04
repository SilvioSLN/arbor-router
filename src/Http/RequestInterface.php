<?php

declare(strict_types=1);

namespace Arbor\Router\Http;

/**
 * Interface agnóstica para abstração de requisições HTTP.
 *
 * Define o contrato mínimo que qualquer implementação de Request deve
 * satisfazer, seja usando superglobais PHP ou adaptando PSR-7.
 *
 * @package Arbor\Router\Http
 */
interface RequestInterface
{
    /**
     * Retorna o método HTTP da requisição (GET, POST, PUT, DELETE, PATCH, etc).
     *
     * @return string O método HTTP em uppercase
     */
    public function method(): string;

    /**
     * Retorna a URI completa da requisição (path + query string).
     *
     * @return string A URI completa
     */
    public function uri(): string;

    /**
     * Retorna apenas o path da URI, sem query string, normalizado.
     *
     * Exemplos:
     *  - "/users/123" (sem trailing slash)
     *  - "/" (raiz)
     *
     * @return string O path normalizado
     */
    public function path(): string;

    /**
     * Retorna os parâmetros da query string (equivalente a $_GET).
     *
     * @return array<string, mixed> Parâmetros GET
     */
    public function query(): array;

    /**
     * Retorna os dados do corpo da requisição (equivalente a $_POST ou parsed body).
     *
     * @return array<string, mixed> Dados do corpo
     */
    public function body(): array;

    /**
     * Retorna o corpo bruto da requisição (raw input).
     *
     * @return string Corpo bruto
     */
    public function rawBody(): string;

    /**
     * Retorna todos os headers HTTP da requisição.
     *
     * @return array<string, string> Headers normalizados (chave lowercase)
     */
    public function headers(): array;

    /**
     * Retorna o valor de um header específico.
     *
     * A busca é case-insensitive.
     *
     * @param string $name Nome do header
     * @return string|null Valor do header ou null se não presente
     */
    public function header(string $name): ?string;

    /**
     * Retorna o Content-Type da requisição.
     *
     * @return string|null Content-Type ou null
     */
    public function contentType(): ?string;

    /**
     * Retorna o valor do header Accept.
     *
     * @return string|null Accept header ou null
     */
    public function accept(): ?string;

    /**
     * Verifica se a requisição é uma chamada AJAX/XHR.
     *
     * @return bool True se X-Requested-With: XMLHttpRequest
     */
    public function isAjax(): bool;

    /**
     * Retorna os arquivos enviados na requisição.
     *
     * @return array<string, mixed> Arquivos ($_FILES)
     */
    public function files(): array;
}
