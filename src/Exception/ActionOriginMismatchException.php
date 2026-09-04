<?php

declare(strict_types=1);

namespace Arbor\Router\Exception;

class ActionOriginMismatchException extends ForbiddenException
{
    public function __construct(string $message = 'Action origin mismatch', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
