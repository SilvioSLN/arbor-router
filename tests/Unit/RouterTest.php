<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Router;
use Arbor\Router\Cache\NullCache;
use Arbor\Router\Http\Request;
use Arbor\Router\Http\Response;

class RouterTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/arbor_router_main_test';
        if (!is_dir($this->fixtureDir)) mkdir($this->fixtureDir, 0777, true);
    }
    
    protected function tearDown(): void
    {
        array_map('unlink', glob("$this->fixtureDir/*.*"));
        rmdir($this->fixtureDir);
    }
    
    public function testRouterDispatchesToPage(): void
    {
        file_put_contents($this->fixtureDir . '/page.php', '<h1>Index</h1>');
        
        $router = new Router([
            'appDir' => $this->fixtureDir,
            'cacheInstance' => new NullCache(),
            'security' => ['headers' => false]
        ]);
        
        $request = Request::create('GET', '/', [], [], []);
        $response = $router->handle($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('<h1>Index</h1>', $response->body());
        $this->assertEquals(200, $response->statusCode());
    }
    
    public function testRouterHandles404(): void
    {
        $router = new Router([
            'appDir' => $this->fixtureDir,
            'cacheInstance' => new NullCache(),
            'security' => ['headers' => false]
        ]);
        
        $request = Request::create('GET', '/missing', [], [], []);
        $response = $router->handle($request);
        
        $this->assertEquals(404, $response->statusCode());
        $this->assertStringContainsString('404', $response->body());
    }
}
