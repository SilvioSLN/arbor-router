<?php

declare(strict_types=1);

namespace Arbor\Router\Action;

use Arbor\Router\Http\JsonResponse;
use Arbor\Router\Http\RedirectResponse;
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\Response;
use Arbor\Router\Rendering\LayoutRenderer;
use Arbor\Router\Validation\Validator;
use Arbor\Router\Sanitizer\Sanitizer;

/**
 * Handler para processamento de actions (action.php).
 *
 * Orquestra o fluxo completo de uma action:
 * 1. Validação de segurança (via ActionGuard)
 * 2. Inclusão e execução do action.php
 * 3. Processamento do resultado (array → ActionResult)
 * 4. Geração da resposta (JSON para AJAX ou redirect)
 *
 * O action.php recebe as mesmas variáveis injetadas de um page.php
 * ($request, $params, $query, $body, $validator, $sanitizer) e deve
 * retornar um array ou ActionResult.
 *
 * @package Arbor\Router\Action
 */
class ActionHandler
{
    public function __construct(
        private readonly ActionGuard $actionGuard,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly Validator $validator,
        private readonly Sanitizer $sanitizer,
    ) {}

    /**
     * Retorna a instância do LayoutRenderer.
     */
    public function layoutRenderer(): LayoutRenderer
    {
        return $this->layoutRenderer;
    }

    /**
     * Processa uma action.
     *
     * @param string $filePath Caminho do action.php
     * @param string $actionPath Path da rota onde a action reside
     * @param RequestInterface $request Requisição HTTP
     * @param array<string, mixed> $params Parâmetros dinâmicos da rota
     * @return Response Resposta HTTP (JSON para AJAX, redirect, ou HTML)
     */
    public function handle(
        string $filePath,
        string $actionPath,
        RequestInterface $request,
        array $params = [],
    ): Response {
        // 1. Validação de segurança (header + method + path + CSRF)
        $this->actionGuard->validate($request, $actionPath);

        // 2. Executa o action.php
        $variables = [
            'params' => $params,
            'request' => $request,
            'query' => $request->query(),
            'body' => $request->body(),
            'validator' => $this->validator,
            'sanitizer' => $this->sanitizer,
        ];

        $result = $this->executeAction($filePath, $variables);

        // 3. Converte resultado para ActionResult
        $actionResult = $this->normalizeResult($result);

        // 4. Gera a resposta apropriada
        return $this->buildResponse($actionResult, $request);
    }

    /**
     * Executa o action.php e captura o resultado.
     *
     * @param string $filePath Caminho do action.php
     * @param array<string, mixed> $variables Variáveis a injetar
     * @return mixed Resultado da execução
     */
    private function executeAction(string $filePath, array $variables): mixed
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Action file not found: {$filePath}");
        }

        $execute = static function (string $__file, array $__vars): mixed {
            extract($__vars, EXTR_SKIP);
            ob_start();
            $__result = include $__file;
            $__output = ob_get_clean();

            if (is_callable($__result)) {
                $__result = call_user_func($__result, $__vars['request'] ?? null, $__vars['params'] ?? []);
            }

            // Se a action retornar um ActionResult, array, etc., retorna ele
            // ignorando qualquer output acidental
            if ($__result !== 1 && $__result !== true) {
                return $__result;
            }

            return $__output;
        };

        return $execute($filePath, $variables);
    }

    /**
     * Normaliza o resultado de uma action para ActionResult.
     *
     * Aceita:
     * - ActionResult → retornado como está
     * - array → convertido via ActionResult::fromArray()
     * - string → interpretado como mensagem de sucesso
     * - null → resultado de sucesso vazio
     *
     * @param mixed $result Resultado bruto da action
     * @return ActionResult Resultado normalizado
     */
    private function normalizeResult(mixed $result): ActionResult
    {
        if ($result instanceof ActionResult) {
            return $result;
        }

        if (is_array($result)) {
            return ActionResult::fromArray($result);
        }

        if (is_string($result)) {
            return ActionResult::success($result);
        }

        return ActionResult::success();
    }

    /**
     * Constrói a resposta HTTP baseada no ActionResult.
     *
     * Para requisições AJAX (X-Requested-With: XMLHttpRequest):
     * → Retorna JSON com os dados do ActionResult
     *
     * Para requisições normais com redirect:
     * → Retorna RedirectResponse
     *
     * Para requisições normais sem redirect:
     * → Retorna JSON (padrão para actions sem redirect)
     *
     * @param ActionResult $result Resultado da action
     * @param RequestInterface $request Requisição original
     * @return Response Resposta HTTP
     */
    private function buildResponse(ActionResult $result, RequestInterface $request): Response
    {
        // Se tem redirect e não é AJAX, faz redirect HTTP
        if ($result->hasRedirect() && !$request->isAjax()) {
            return new RedirectResponse(
                $result->getRedirectUrl(),
                $result->isSuccess() ? 302 : 303,
            );
        }

        // Para AJAX ou quando não há redirect, retorna JSON
        return new JsonResponse(
            $result->toArray(),
            $result->getHttpStatusCode(),
        );
    }
}
