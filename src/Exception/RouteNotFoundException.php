<?php

declare(strict_types=1);

namespace Arbor\Router\Exception;

class RouteNotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Route not found', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
