<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Rendering;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Rendering\ApiRenderer;
use Arbor\Router\Rendering\ContentNegotiator;
use Arbor\Router\Http\Request;
use Arbor\Router\Http\JsonResponse;

class ApiRendererTest extends TestCase
{
    private string $fixtureDir;
    private ApiRenderer $renderer;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/arbor_router_apirenderer_test';
        if (!is_dir($this->fixtureDir)) mkdir($this->fixtureDir, 0777, true);
        $this->renderer = new ApiRenderer(new ContentNegotiator());
    }
    
    protected function tearDown(): void
    {
        array_map('unlink', glob("$this->fixtureDir/*.*"));
        rmdir($this->fixtureDir);
    }
    
    public function testRendersArrayAsJson(): void
    {
        $file = $this->fixtureDir . '/route.php';
        file_put_contents($file, '<?php return ["GET" => function() { return ["ok" => true]; }];');
        
        $request = Request::create('GET', '/api', [], [], ['ACCEPT' => 'application/json']);
        $response = $this->renderer->render($file, $request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('{"ok":true}', $response->body());
    }
    
    public function testThrowsMethodNotAllowed(): void
    {
        $file = $this->fixtureDir . '/route.php';
        file_put_contents($file, '<?php return ["POST" => function() { return []; }];');
        
        $request = Request::create('GET', '/api', [], [], []);
        
        $this->expectException(\Arbor\Router\Exception\MethodNotAllowedException::class);
        $this->renderer->render($file, $request);
    }
    
    public function testRendersExplicitResponseObject(): void
    {
        $file = $this->fixtureDir . '/route.php';
        file_put_contents($file, '<?php return ["GET" => function() { return new \Arbor\Router\Http\TextResponse("ok"); }];');
        
        $request = Request::create('GET', '/api', [], [], []);
        $response = $this->renderer->render($file, $request);
        
        $this->assertInstanceOf(\Arbor\Router\Http\TextResponse::class, $response);
        $this->assertEquals('ok', $response->body());
    }
}
