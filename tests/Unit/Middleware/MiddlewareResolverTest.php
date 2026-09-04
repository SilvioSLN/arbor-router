<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Middleware\MiddlewareResolver;
use Arbor\Router\Middleware\MiddlewarePipeline;

class MiddlewareResolverTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/arbor_router_middleware_resolver_test';
        if (!is_dir($this->fixtureDir)) {
            mkdir($this->fixtureDir, 0777, true);
        }
    }
    
    protected function tearDown(): void
    {
        $files = glob($this->fixtureDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        rmdir($this->fixtureDir);
    }
    
    public function testResolverLoadsMiddleware(): void
    {
        $m1 = $this->fixtureDir . '/m1.php';
        $m2 = $this->fixtureDir . '/m2.php';
        
        file_put_contents($m1, "<?php return [function(\$req, \$next) { return \$next(\$req); }];");
        file_put_contents($m2, "<?php return function(\$req, \$next) { return \$next(\$req); };");
        
        $resolver = new MiddlewareResolver();
        $middlewares = $resolver->resolve([$m1, $m2, $this->fixtureDir . '/not-found.php']);
        
        $this->assertCount(2, $middlewares);
        $this->assertIsCallable($middlewares[0]);
        $this->assertIsCallable($middlewares[1]);
    }

    public function testResolverCreatesPipeline(): void
    {
        $m1 = $this->fixtureDir . '/m1.php';
        file_put_contents($m1, "<?php return [function(\$req, \$next) { return \$next(\$req); }];");
        
        $resolver = new MiddlewareResolver();
        $pipeline = $resolver->createPipeline([$m1]);
        
        $this->assertInstanceOf(MiddlewarePipeline::class, $pipeline);
        $this->assertEquals(1, $pipeline->count());
    }
}
