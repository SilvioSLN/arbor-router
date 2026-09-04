<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Cache\FileCache;

class FileCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/arbor_cache_test';
        $this->cleanCache();
    }
    
    protected function tearDown(): void
    {
        $this->cleanCache();
    }
    
    private function cleanCache(): void
    {
        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob("$this->cacheDir/*.cache"));
            rmdir($this->cacheDir);
        }
    }

    public function testSetAndGet(): void
    {
        $cache = new FileCache($this->cacheDir);
        
        $this->assertNull($cache->get('missing'));
        
        $cache->set('key1', ['data' => 123]);
        
        $this->assertTrue($cache->has('key1'));
        $this->assertEquals(['data' => 123], $cache->get('key1'));
        
        $cache->delete('key1');
        $this->assertFalse($cache->has('key1'));
    }
}
