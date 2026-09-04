<?php

declare(strict_types=1);

namespace Arbor\Router\Rendering;

use Arbor\Router\Http\JsonResponse;
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\Response;
use Arbor\Router\Http\TextResponse;
use Arbor\Router\Http\XmlResponse;
use Arbor\Router\Exception\MethodNotAllowedException;

/**
 * Renderizador de rotas de API (route.php).
 *
 * Processa rotas de API que definem funções nomeadas por método HTTP
 * (GET, POST, PUT, DELETE, PATCH, etc). O retorno pode ser:
 *
 * 1. **Dados brutos** (array/objeto) → serializado automaticamente
 *    baseado no header Accept (JSON, XML, TXT)
 * 2. **Response explícito** → retornado diretamente ao cliente
 *
 * Formato do route.php:
 * ```php
 * function GET($request, $params, $query) {
 *     return ['users' => [...]]; // Serializado automaticamente
 * }
 *
 * function POST($request, $params, $body) {
 *     return new JsonResponse(['created' => true], 201);
 * }
 * ```
 *
 * @package Arbor\Router\Rendering
 */
class ApiRenderer implements RendererInterface
{
    public function __construct(
        private readonly ContentNegotiator $contentNegotiator,
    ) {}

    /**
     * {@inheritdoc}
     *
     * Renderiza uma rota de API:
     * 1. Inclui o route.php para registrar as funções
     * 2. Verifica se existe uma função para o método HTTP da requisição
     * 3. Executa a função com os parâmetros
     * 4. Se o retorno é um Response, retorna diretamente
     * 5. Se o retorno são dados brutos, aplica negociação de conteúdo
     */
    public function render(
        string $filePath,
        RequestInterface $request,
        array $params = [],
        array $extraVars = [],
    ): Response {
        $method = strtoupper($request->method());

        // Inclui o arquivo para registrar as funções no escopo global
        // Usa um namespace isolado para evitar conflitos
        $functions = $this->loadRouteFunctions($filePath);

        // Verifica se existe handler para o método
        if (!isset($functions[$method])) {
            $allowed = array_keys($functions);
            throw new MethodNotAllowedException(
                "Method {$method} not allowed. Allowed: " . implode(', ', $allowed),
                allowedMethods: $allowed,
            );
        }

        // Executa o handler
        $handler = $functions[$method];
        $result = $handler($request, $params, $request->query(), $request->body());

        // Se já é um Response, retorna diretamente
        if ($result instanceof Response) {
            return $result;
        }

        // Serializa automaticamente baseado no Accept header
        return $this->serializeResult($result, $request);
    }

    /**
     * Carrega as funções definidas em um route.php.
     *
     * Inclui o arquivo e detecta quais funções nomeadas por método HTTP
     * foram definidas (GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS).
     *
     * @param string $filePath Caminho do route.php
     * @return array<string, callable> Mapa de método → callable
     */
    private function loadRouteFunctions(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Route file not found: {$filePath}");
        }

        $beforeFunctions = get_defined_functions()['user'];

        // include returns the value returned by the included file
        $returned = include $filePath;

        // Check if the file returned an array mapping methods to closures
        if (is_array($returned)) {
            return $this->parseReturnedFunctions($returned);
        }

        $afterFunctions = get_defined_functions()['user'];
        $newFunctions = array_diff($afterFunctions, $beforeFunctions);

        $httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
        $functions = [];

        foreach ($newFunctions as $funcName) {
            $upperName = strtoupper($funcName);
            $shortName = substr($upperName, strrpos($upperName, '\\') + 1) ?: $upperName;

            if (in_array($shortName, $httpMethods, true)) {
                $functions[$shortName] = $funcName;
            }
        }

        return $functions;
    }

    /**
     * Parseia funções retornadas pelo route.php como array.
     */
    private function parseReturnedFunctions(array $result): array
    {
        $functions = [];
        $httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];

        foreach ($result as $method => $handler) {
            $method = strtoupper((string) $method);
            if (in_array($method, $httpMethods, true) && is_callable($handler)) {
                $functions[$method] = $handler;
            }
        }

        return $functions;
    }

    /**
     * Serializa dados brutos para o formato negociado.
     *
     * @param mixed $data Dados a serializar
     * @param RequestInterface $request Requisição com header Accept
     * @return Response Resposta serializada
     */
    private function serializeResult(mixed $data, RequestInterface $request): Response
    {
        $format = $this->contentNegotiator->negotiate($request->accept());

        return match ($format) {
            ContentNegotiator::FORMAT_JSON => new JsonResponse($data),
            ContentNegotiator::FORMAT_XML => new XmlResponse($data),
            ContentNegotiator::FORMAT_TEXT => new TextResponse(
                is_string($data) ? $data : print_r($data, true)
            ),
            ContentNegotiator::FORMAT_HTML => new Response(
                is_string($data) ? $data : print_r($data, true),
                200,
                ['content-type' => 'text/html; charset=utf-8'],
            ),
            default => new JsonResponse($data),
        };
    }
}
