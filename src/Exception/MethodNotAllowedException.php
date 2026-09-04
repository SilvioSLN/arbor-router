<?php

declare(strict_types=1);

namespace Arbor\Router\Exception;

class MethodNotAllowedException extends \RuntimeException
{
    /** @param string[] $allowedMethods */
    public function __construct(
        string $message = 'Method not allowed',
        int $code = 405,
        ?\Throwable $previous = null,
        public readonly array $allowedMethods = [],
    ) {
        parent::__construct($message, $code, $previous);
    }
}
