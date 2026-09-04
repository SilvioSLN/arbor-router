<?php

declare(strict_types=1);

namespace Arbor\Router\Exception;

use Arbor\Router\Validation\ValidationResult;

class ValidationException extends \RuntimeException
{
    public function __construct(
        string $message = 'Validation failed',
        int $code = 422,
        ?\Throwable $previous = null,
        public readonly ?ValidationResult $result = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
