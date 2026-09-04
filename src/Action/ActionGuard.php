<?php

declare(strict_types=1);

namespace Arbor\Router\Action;

use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Exception\ForbiddenException;
use Arbor\Router\Exception\ActionOriginMismatchException;
use Arbor\Router\Security\CsrfGuard;

/**
 * Security guard for action endpoints (action.php).
 *
 * Validates action requests across 4 security dimensions:
 * 1. HTTP Method: Only mutation methods allowed (POST, PUT, DELETE, PATCH).
 * 2. Origin / Path: Ensures the request path matches the targeted action path.
 * 3. Header: If present, verifies X-Action-Request header value (optional for HTML forms).
 * 4. CSRF: Verifies CSRF token via session (if CsrfGuard is configured).
 *
 * @package Arbor\Router\Action
 */
class ActionGuard
{
    private const ALLOWED_METHODS = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * @param string $headerName Custom header name (default: 'X-Action-Request')
     * @param string $headerValue Expected header value (default: 'true')
     * @param CsrfGuard|null $csrfGuard Optional CSRF guard for form/token verification
     */
    public function __construct(
        private readonly string $headerName = 'X-Action-Request',
        private readonly string $headerValue = 'true',
        private readonly ?CsrfGuard $csrfGuard = null,
    ) {}

    /**
     * Validates an incoming action request.
     *
     * @param RequestInterface $request Incoming HTTP request
     * @param string $actionPath Route path where the action is defined
     * @throws ForbiddenException When method is disallowed, header is invalid, or CSRF fails
     * @throws ActionOriginMismatchException When request path does not match action path
     */
    public function validate(RequestInterface $request, string $actionPath): void
    {
        $this->validateMethod($request);
        $this->validateOrigin($request, $actionPath);
        $this->validateHeader($request);
        $this->validateCsrf($request);
    }

    /**
     * Validates that the HTTP method is allowed for mutations.
     *
     * @throws ForbiddenException
     */
    private function validateMethod(RequestInterface $request): void
    {
        $method = strtoupper($request->method());

        if (!in_array($method, self::ALLOWED_METHODS, true)) {
            throw new ForbiddenException(
                "HTTP method '{$method}' is not allowed for actions. " .
                'Allowed methods: ' . implode(', ', self::ALLOWED_METHODS)
            );
        }
    }

    /**
     * Validates that the request path matches the action's defined path.
     *
     * @throws ActionOriginMismatchException
     */
    private function validateOrigin(RequestInterface $request, string $actionPath): void
    {
        if ($this->normalizePath($request->path()) !== $this->normalizePath($actionPath)) {
            throw new ActionOriginMismatchException(
                "Action origin mismatch: request path '{$request->path()}' does not match action path '{$actionPath}'."
            );
        }
    }

    /**
     * Validates the action request header when provided.
     * Allows missing header to support native HTML form submissions without JavaScript.
     *
     * @throws ForbiddenException
     */
    private function validateHeader(RequestInterface $request): void
    {
        $headerValue = $request->header($this->headerName);

        if ($headerValue === null) {
            return;
        }

        if (strtolower($headerValue) !== strtolower($this->headerValue)) {
            throw new ForbiddenException(
                "Action request header '{$this->headerName}' has invalid value. " .
                "Expected '{$this->headerValue}', got '{$headerValue}'."
            );
        }
    }

    /**
     * Validates CSRF token if a CsrfGuard is configured.
     *
     * @throws \Arbor\Router\Exception\CsrfTokenMismatchException
     */
    private function validateCsrf(RequestInterface $request): void
    {
        if ($this->csrfGuard === null) {
            return;
        }

        $this->csrfGuard->validate($request);
    }

    /**
     * Normalizes a URL path by ensuring a leading slash and stripping trailing slash.
     */
    private function normalizePath(string $path): string
    {
        $trimmed = trim($path, '/');
        return $trimmed === '' ? '/' : '/' . $trimmed;
    }

    /**
     * Checks whether the request explicitly carries the action header.
     */
    public function isActionRequest(RequestInterface $request): bool
    {
        $headerValue = $request->header($this->headerName);
        return $headerValue !== null
            && strtolower($headerValue) === strtolower($this->headerValue);
    }
}
