<?php

declare(strict_types=1);

namespace Arbor\Router\Cache;

/**
 * Cache baseado em arquivo serializado.
 *
 * Armazena dados serializados em arquivos no sistema de arquivos.
 * Adequado para produção com volumes médios de cache.
 *
 * @package Arbor\Router\Cache
 */
class FileCache implements CacheInterface
{
    public function __construct(
        private readonly string $cacheDir,
    ) {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));

        // Verifica TTL
        if (isset($data['ttl']) && $data['ttl'] > 0 && time() > $data['expires']) {
            $this->delete($key);
            return null;
        }

        return $data['value'] ?? null;
    }

    public function set(string $key, mixed $value, int $ttl = 0): void
    {
        $file = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'ttl' => $ttl,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ];

        file_put_contents($file, serialize($data), LOCK_EX);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function clear(): void
    {
        $files = glob($this->cacheDir . '/*.cache');
        if ($files === false) {
            return;
        }
        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function getFilePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
