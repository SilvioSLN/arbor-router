<?php

declare(strict_types=1);

namespace Arbor\Router\Security;

/**
 * Gerenciador de headers de segurança HTTP.
 *
 * Aplica automaticamente headers de segurança nas respostas para
 * proteger contra ataques comuns (clickjacking, MIME sniffing, etc).
 *
 * @package Arbor\Router\Security
 */
class SecurityManager
{
    /**
     * Headers de segurança padrão.
     */
    private const DEFAULT_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '0',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    /**
     * @param array<string, string> $customHeaders Headers customizados/override
     * @param bool $enabled Se os headers de segurança estão habilitados
     */
    public function __construct(
        private readonly array $customHeaders = [],
        private readonly bool $enabled = true,
    ) {}

    /**
     * Retorna os headers de segurança a serem aplicados.
     *
     * @return array<string, string> Headers de segurança
     */
    public function getHeaders(): array
    {
        if (!$this->enabled) {
            return [];
        }

        return array_merge(self::DEFAULT_HEADERS, $this->customHeaders);
    }

    /**
     * Aplica os headers de segurança na resposta PHP.
     *
     * Deve ser chamado antes de qualquer output.
     */
    public function applyHeaders(): void
    {
        if (!$this->enabled || headers_sent()) {
            return;
        }

        foreach ($this->getHeaders() as $name => $value) {
            header("{$name}: {$value}");
        }
    }
}
