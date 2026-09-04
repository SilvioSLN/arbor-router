<?php

declare(strict_types=1);

namespace Arbor\Router\Http;

/**
 * Implementação concreta de Request usando superglobais PHP.
 *
 * Encapsula $_SERVER, $_GET, $_POST, $_FILES e php://input para
 * fornecer uma API limpa e testável. Parseia headers automaticamente
 * a partir de $_SERVER (HTTP_* entries).
 *
 * Utiliza PHP 8.4 property hooks para acesso elegante a propriedades
 * computadas de forma lazy.
 *
 * @package Arbor\Router\Http
 */
final class Request implements RequestInterface
{
    /**
     * Headers HTTP parseados e normalizados.
     *
     * @var array<string, string>
     */
    private array $parsedHeaders;

    /**
     * Corpo bruto da requisição (cached).
     */
    private ?string $cachedRawBody = null;

    /**
     * Corpo parseado da requisição (cached).
     *
     * @var array<string, mixed>|null
     */
    private ?array $parsedBody = null;

    /**
     * Cria uma nova instância de Request.
     *
     * @param array<string, mixed> $server Dados do servidor (default: $_SERVER)
     * @param array<string, mixed> $get Parâmetros GET (default: $_GET)
     * @param array<string, mixed> $post Parâmetros POST (default: $_POST)
     * @param array<string, mixed> $filesData Arquivos (default: $_FILES)
     * @param string|null $rawInput Corpo bruto (default: php://input)
     */
    public function __construct(
        private readonly array $server = [],
        private readonly array $get = [],
        private readonly array $post = [],
        private readonly array $filesData = [],
        private readonly ?string $rawInput = null,
    ) {
        $this->parsedHeaders = $this->parseHeaders();
    }

    /**
     * Cria Request a partir das superglobais PHP.
     *
     * Factory method para uso em produção.
     *
     * @return self Nova instância de Request
     */
    public static function fromGlobals(): self
    {
        return new self(
            server: $_SERVER,
            get: $_GET,
            post: $_POST,
            filesData: $_FILES,
            rawInput: null,
        );
    }

    /**
     * Cria Request a partir de dados explícitos (útil para testes).
     *
     * @param string $method Método HTTP
     * @param string $uri URI da requisição
     * @param array<string, mixed> $query Parâmetros GET
     * @param array<string, mixed> $body Dados POST
     * @param array<string, string> $headers Headers HTTP
     * @param string $rawBody Corpo bruto
     * @return self Nova instância de Request
     */
    public static function create(
        string $method = 'GET',
        string $uri = '/',
        array $query = [],
        array $body = [],
        array $headers = [],
        string $rawBody = '',
    ): self {
        $server = [
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $uri,
            'QUERY_STRING' => http_build_query($query),
        ];

        // Converte headers para formato $_SERVER (HTTP_*)
        foreach ($headers as $name => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $server[$key] = $value;
        }

        return new self(
            server: $server,
            get: $query,
            post: $body,
            filesData: [],
            rawInput: $rawBody,
        );
    }

    /** {@inheritdoc} */
    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    /** {@inheritdoc} */
    public function uri(): string
    {
        return (string) ($this->server['REQUEST_URI'] ?? '/');
    }

    /**
     * {@inheritdoc}
     *
     * Normaliza o path removendo query string e trailing slash.
     * Garante que "/" é retornado para a raiz.
     */
    public function path(): string
    {
        $uri = $this->uri();
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Remove trailing slash, exceto para raiz
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /** {@inheritdoc} */
    public function query(): array
    {
        return $this->get;
    }

    /**
     * {@inheritdoc}
     *
     * Para requisições com Content-Type application/json, faz decode
     * automático do corpo bruto. Caso contrário, retorna $_POST.
     */
    public function body(): array
    {
        if ($this->parsedBody !== null) {
            return $this->parsedBody;
        }

        $contentType = $this->contentType();

        if ($contentType !== null && str_contains($contentType, 'application/json')) {
            $raw = $this->rawBody();
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                $this->parsedBody = is_array($decoded) ? $decoded : [];
            } else {
                $this->parsedBody = [];
            }
        } else {
            $this->parsedBody = $this->post;
        }

        return $this->parsedBody;
    }

    /** {@inheritdoc} */
    public function rawBody(): string
    {
        if ($this->cachedRawBody !== null) {
            return $this->cachedRawBody;
        }

        $this->cachedRawBody = $this->rawInput ?? (file_get_contents('php://input') ?: '');

        return $this->cachedRawBody;
    }

    /** {@inheritdoc} */
    public function headers(): array
    {
        return $this->parsedHeaders;
    }

    /**
     * {@inheritdoc}
     *
     * Busca case-insensitive: converte o nome para lowercase.
     */
    public function header(string $name): ?string
    {
        $normalized = strtolower($name);
        return $this->parsedHeaders[$normalized] ?? null;
    }

    /** {@inheritdoc} */
    public function contentType(): ?string
    {
        return $this->header('content-type');
    }

    /** {@inheritdoc} */
    public function accept(): ?string
    {
        return $this->header('accept');
    }

    /** {@inheritdoc} */
    public function isAjax(): bool
    {
        return strtolower($this->header('x-requested-with') ?? '') === 'xmlhttprequest';
    }

    /** {@inheritdoc} */
    public function files(): array
    {
        return $this->filesData;
    }

    /**
     * Parseia headers HTTP a partir do array $_SERVER.
     *
     * Converte entries HTTP_* para nomes lowercase com hifens.
     * Exemplo: HTTP_ACCEPT_LANGUAGE → accept-language
     *
     * Também captura CONTENT_TYPE e CONTENT_LENGTH que não possuem
     * prefixo HTTP_ no $_SERVER.
     *
     * @return array<string, string> Headers normalizados
     */
    private function parseHeaders(): array
    {
        $headers = [];

        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }

        // Headers especiais sem prefixo HTTP_
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $this->server['CONTENT_TYPE'];
        }
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string) $this->server['CONTENT_LENGTH'];
        }

        return $headers;
    }
}
