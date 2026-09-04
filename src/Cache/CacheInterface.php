<?php

declare(strict_types=1);

namespace Arbor\Router\Cache;

/**
 * Interface para cache de rotas.
 *
 * @package Arbor\Router\Cache
 */
interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 0): void;
    public function has(string $key): bool;
    public function delete(string $key): void;
    public function clear(): void;
}
