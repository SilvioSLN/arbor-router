<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Action;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Action\ActionHandler;
use Arbor\Router\Action\ActionGuard;
use Arbor\Router\Rendering\LayoutRenderer;
use Arbor\Router\Validation\Validator;
use Arbor\Router\Sanitizer\Sanitizer;
use Arbor\Router\Http\Request;
use Arbor\Router\Http\JsonResponse;
use Arbor\Router\Http\RedirectResponse;

class ActionHandlerTest extends TestCase
{
    private string $fixtureDir;
    private ActionHandler $handler;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/arbor_router_action_test';
        if (!is_dir($this->fixtureDir)) mkdir($this->fixtureDir, 0777, true);
        
        $guard = new ActionGuard('X-Action-Request', 'true', null);
        $renderer = new LayoutRenderer();
        
        $this->handler = new ActionHandler($guard, $renderer, new Validator(), new Sanitizer());
    }
    
    protected function tearDown(): void
    {
        array_map('unlink', glob("$this->fixtureDir/*.*"));
        rmdir($this->fixtureDir);
    }
    
    public function testReturnsJsonResponseForAjaxWithRedirect(): void
    {
        $file = $this->fixtureDir . '/action.php';
        file_put_contents($file, '<?php return \Arbor\Router\Action\ActionResult::success()->redirect("/home");');
        
        $request = Request::create('POST', '/login', [], [], [
            'X-Action-Request' => 'true',
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        
        $response = $this->handler->handle($file, '/login', $request);
        
        // Como é ajax, mesmo tendo redirect, deve retornar JSON com a URL de redirect para o frontend tratar
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertStringContainsString('/home', $response->body());
    }
    
    public function testReturnsRedirectResponseForNormalPost(): void
    {
        $file = $this->fixtureDir . '/action.php';
        file_put_contents($file, '<?php return \Arbor\Router\Action\ActionResult::success()->redirect("/home");');
        
        $request = Request::create('POST', '/login', [], [], [
            'X-Action-Request' => 'true' // Normal request, not ajax
        ]);
        
        $response = $this->handler->handle($file, '/login', $request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->statusCode());
        $this->assertEquals('/home', $response->headers()['location']);
    }
}
