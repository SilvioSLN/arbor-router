<?php

declare(strict_types=1);

namespace Arbor\Router\Action;

/**
 * Value Object representando o resultado de uma action.
 *
 * Suporta dois modos:
 * 1. **Fluent API tipada** — para controle preciso do resultado
 * 2. **Conversão a partir de array** — para simplicidade
 *
 * Exemplos:
 * ```php
 * // Fluent API
 * ActionResult::success('Usuário criado')->redirect('/users/123');
 * ActionResult::error(['email' => 'Já existe'])->statusCode(422);
 * ActionResult::success()->data(['id' => 123]);
 *
 * // A partir de array (retornado pelo action.php)
 * ActionResult::fromArray([
 *     'success' => true,
 *     'message' => 'Criado',
 *     'redirect' => '/users/123',
 * ]);
 * ```
 *
 * @package Arbor\Router\Action
 */
/**
 * Value Object representing the outcome of an action execution.
 *
 * Supports two idiomatic modes:
 * 1. **Typed Fluent API** — for explicit and structured control over mutations:
 *    ```php
 *    return ActionResult::success('User created successfully')
 *        ->data(['id' => $user->id])
 *        ->redirect('/users/' . $user->id);
 *    ```
 * 2. **Array Conversion** — for concise controller-less returns:
 *    ```php
 *    return ActionResult::fromArray([
 *        'success'  => true,
 *        'message'  => 'Saved',
 *        'redirect' => '/dashboard',
 *    ]);
 *    ```
 *
 * @package Arbor\Router\Action
 */
final class ActionResult
{
    /**
     * @param bool $isSuccess Whether the action succeeded
     * @param string|null $message Optional user feedback message
     * @param array<string, string|string[]> $errors Field-specific validation or business error messages
     * @param array<string, mixed> $data Extra payload data returned to caller
     * @param string|null $redirectUrl Optional destination URL for redirection
     * @param int $httpStatusCode HTTP response status code (default: 200 on success, 422 on error)
     */
    public function __construct(
        private bool $isSuccess = true,
        private ?string $message = null,
        private array $errors = [],
        private array $data = [],
        private ?string $redirectUrl = null,
        private int $httpStatusCode = 200,
    ) {}

    /**
     * Creates a successful action result.
     *
     * @param string|null $message Optional success message
     * @return self
     */
    public static function success(?string $message = null): self
    {
        return new self(isSuccess: true, message: $message);
    }

    /**
     * Creates an error action result.
     *
     * @param array<string, string|string[]> $errors Validation or business errors keyed by field name
     * @param string|null $message General error summary message
     * @return self
     */
    public static function error(array $errors = [], ?string $message = null): self
    {
        return new self(
            isSuccess: false,
            message: $message,
            errors: $errors,
            httpStatusCode: 422,
        );
    }

    /**
     * Creates an ActionResult from an associative array (e.g. returned by action.php).
     *
     * Recognized keys:
     * - 'success' (bool): true if action succeeded
     * - 'message' (string): feedback message
     * - 'errors' (array): field error messages
     * - 'data' (array): additional data
     * - 'redirect' (string): redirect target URL
     * - 'status' (int): HTTP status code
     *
     * @param array{
     *     success?: bool,
     *     message?: string,
     *     errors?: array<string, string|string[]>,
     *     data?: array<string, mixed>,
     *     redirect?: string,
     *     status?: int
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isSuccess: (bool) ($data['success'] ?? true),
            message: $data['message'] ?? null,
            errors: (array) ($data['errors'] ?? []),
            data: (array) ($data['data'] ?? []),
            redirectUrl: $data['redirect'] ?? null,
            httpStatusCode: (int) ($data['status'] ?? ($data['success'] ?? true ? 200 : 422)),
        );
    }

    /**
     * Define dados adicionais de retorno.
     *
     * @return $this Fluent interface
     */
    public function data(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Define URL de redirecionamento.
     *
     * @return $this Fluent interface
     */
    public function redirect(string $url): static
    {
        $this->redirectUrl = $url;
        return $this;
    }

    /**
     * Define o HTTP status code.
     *
     * @return $this Fluent interface
     */
    public function statusCode(int $code): static
    {
        $this->httpStatusCode = $code;
        return $this;
    }

    /** Verifica se é sucesso */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /** Verifica se é erro */
    public function isError(): bool
    {
        return !$this->isSuccess;
    }

    /** Retorna a mensagem */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /** Retorna os erros */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** Retorna os dados */
    public function getData(): array
    {
        return $this->data;
    }

    /** Retorna URL de redirect */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /** Verifica se tem redirect */
    public function hasRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }

    /** Retorna o HTTP status code */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Converte o resultado para array (útil para respostas JSON via AJAX).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->isSuccess,
        ];

        if ($this->message !== null) {
            $result['message'] = $this->message;
        }

        if (!empty($this->errors)) {
            $result['errors'] = $this->errors;
        }

        if (!empty($this->data)) {
            $result['data'] = $this->data;
        }

        if ($this->redirectUrl !== null) {
            $result['redirect'] = $this->redirectUrl;
        }

        return $result;
    }
}
