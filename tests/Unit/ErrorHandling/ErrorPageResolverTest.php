<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\ErrorHandling;

use PHPUnit\Framework\TestCase;
use Arbor\Router\ErrorHandling\ErrorPageResolver;

class ErrorPageResolverTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/arbor_router_error_test';
        if (!is_dir($this->fixtureDir)) mkdir($this->fixtureDir, 0777, true);
        if (!is_dir($this->fixtureDir . '/deep')) mkdir($this->fixtureDir . '/deep', 0777, true);
    }
    
    protected function tearDown(): void
    {
        @unlink($this->fixtureDir . '/error.php');
        @unlink($this->fixtureDir . '/deep/not-found.php');
        @rmdir($this->fixtureDir . '/deep');
        @rmdir($this->fixtureDir);
    }

    public function testBubblesUpToFindErrorPage(): void
    {
        file_put_contents($this->fixtureDir . '/error.php', '<?php // root error');
        
        $resolver = new ErrorPageResolver();
        
        // Deve encontrar o error.php na raiz borbulhando a partir de /deep
        $found = $resolver->resolve($this->fixtureDir . '/deep', $this->fixtureDir, 'error.php');
        
        $this->assertEquals(str_replace('\\', '/', $this->fixtureDir) . '/error.php', str_replace('\\', '/', $found));
    }
}
