<?php

declare(strict_types=1);

namespace Arbor\Router\Security;

use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Exception\ForbiddenException;

/**
 * Guarda de segurança para rotas de API.
 *
 * Valida que requisições a route.php possuem o header configurável
 * correto. Isso previne acesso direto via browser a endpoints de API.
 *
 * A validação pode ser:
 * 1. **Valor fixo**: header deve ter um valor literal específico
 * 2. **Callback customizado**: lógica avançada (ex: validação de sessão)
 *
 * @package Arbor\Router\Security
 */
class ApiGuard
{
    /**
     * @param string $headerName Nome do header (default: X-API-Request)
     * @param string $headerValue Valor esperado (default: true)
     * @param (\Closure(RequestInterface, string): bool)|null $customValidator
     *        Callback customizado de validação. Recebe ($request, $headerValue).
     *        Se fornecido, substitui a validação padrão de valor fixo.
     */
    public function __construct(
        private readonly string $headerName = 'X-API-Request',
        private readonly string $headerValue = 'true',
        private readonly ?\Closure $customValidator = null,
    ) {}

    /**
     * Valida que a requisição possui o header de API correto.
     *
     * @param RequestInterface $request Requisição HTTP
     * @throws ForbiddenException Se o header estiver ausente ou inválido
     */
    public function validate(RequestInterface $request): void
    {
        $headerValue = $request->header($this->headerName);

        if ($headerValue === null) {
            throw new ForbiddenException(
                "API request header '{$this->headerName}' is missing. " .
                'API routes require this header for access.'
            );
        }

        // Usa validator customizado se fornecido
        if ($this->customValidator !== null) {
            $isValid = ($this->customValidator)($request, $headerValue);
            if (!$isValid) {
                throw new ForbiddenException(
                    "API request header '{$this->headerName}' validation failed."
                );
            }
            return;
        }

        // Validação padrão por valor fixo
        if (strtolower($headerValue) !== strtolower($this->headerValue)) {
            throw new ForbiddenException(
                "API request header '{$this->headerName}' has invalid value. " .
                "Expected '{$this->headerValue}'."
            );
        }
    }

    /**
     * Verifica se uma requisição possui o header de API (sem validar valor).
     *
     * @param RequestInterface $request Requisição HTTP
     * @return bool True se o header está presente
     */
    public function hasApiHeader(RequestInterface $request): bool
    {
        return $request->header($this->headerName) !== null;
    }
}
