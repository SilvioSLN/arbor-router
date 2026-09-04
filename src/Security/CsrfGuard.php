<?php

declare(strict_types=1);

namespace Arbor\Router\Security;

use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Exception\CsrfTokenMismatchException;

/**
 * Guarda de CSRF (Cross-Site Request Forgery).
 *
 * Gera e valida tokens CSRF para proteger actions e formulários
 * contra ataques de cross-site request forgery.
 *
 * O token é armazenado na sessão PHP e deve ser incluído:
 * - Como campo hidden `_csrf_token` em formulários HTML
 * - Como header `X-CSRF-Token` em requisições AJAX
 *
 * @package Arbor\Router\Security
 */
class CsrfGuard
{
    /**
     * Nome da chave na sessão para o token CSRF.
     */
    private const SESSION_KEY = '_arbor_csrf_token';

    /**
     * Nome do campo de formulário para o token.
     */
    public const FIELD_NAME = '_csrf_token';

    /**
     * Nome do header HTTP para o token.
     */
    public const HEADER_NAME = 'X-CSRF-Token';

    /**
     * Gera ou retorna o token CSRF atual.
     *
     * O token é gerado uma vez por sessão e reutilizado.
     * Para gerar um novo token, use regenerate().
     *
     * @return string Token CSRF (64 caracteres hex)
     */
    public function getToken(): string
    {
        $this->ensureSession();

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = $this->generateToken();
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Regenera o token CSRF (útil após login/logout).
     *
     * @return string Novo token CSRF
     */
    public function regenerate(): string
    {
        $this->ensureSession();
        $_SESSION[self::SESSION_KEY] = $this->generateToken();
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Valida o token CSRF na requisição.
     *
     * Busca o token em duas fontes (em ordem):
     * 1. Header X-CSRF-Token
     * 2. Campo _csrf_token no corpo da requisição
     *
     * Usa timing-safe comparison para prevenir timing attacks.
     *
     * @param RequestInterface $request Requisição HTTP
     * @throws CsrfTokenMismatchException Se o token for inválido ou ausente
     */
    public function validate(RequestInterface $request): void
    {
        $this->ensureSession();

        $expectedToken = $_SESSION[self::SESSION_KEY] ?? null;

        if ($expectedToken === null) {
            throw new CsrfTokenMismatchException(
                'CSRF token not found in session. Ensure the session is started.'
            );
        }

        // Busca token na requisição
        $providedToken = $request->header(self::HEADER_NAME)
            ?? $request->body()[self::FIELD_NAME]
            ?? null;

        if ($providedToken === null) {
            throw new CsrfTokenMismatchException(
                'CSRF token missing from request. Include it as header ' .
                "'" . self::HEADER_NAME . "' or form field '" . self::FIELD_NAME . "'."
            );
        }

        // Comparação timing-safe
        if (!hash_equals($expectedToken, (string) $providedToken)) {
            throw new CsrfTokenMismatchException(
                'CSRF token mismatch. The provided token does not match the session token.'
            );
        }
    }

    /**
     * Gera o HTML de um campo hidden com o token CSRF.
     *
     * @return string Tag HTML <input type="hidden">
     */
    public function field(): string
    {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' . $token . '">';
    }

    /**
     * Gera um token criptograficamente seguro.
     *
     * @return string Token hex de 64 caracteres (32 bytes)
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Garante que a sessão PHP está ativa.
     */
    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
