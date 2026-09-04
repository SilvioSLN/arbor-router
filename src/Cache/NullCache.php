<?php

declare(strict_types=1);

namespace Arbor\Router\Cache;

/**
 * Cache no-op (sem cache).
 *
 * Útil para desenvolvimento onde o scan deve acontecer a cada request.
 *
 * @package Arbor\Router\Cache
 */
class NullCache implements CacheInterface
{
    public function get(string $key): mixed { return null; }
    public function set(string $key, mixed $value, int $ttl = 0): void {}
    public function has(string $key): bool { return false; }
    public function delete(string $key): void {}
    public function clear(): void {}
}
