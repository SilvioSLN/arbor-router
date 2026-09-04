<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Middleware\MiddlewarePipeline;
use Arbor\Router\Middleware\MiddlewareInterface;
use Arbor\Router\Http\Request;
use Arbor\Router\Http\Response;
use Arbor\Router\Http\RequestInterface;

class DummyMiddleware implements MiddlewareInterface
{
    public function __construct(private string $append = '') {}

    public function handle(RequestInterface $request, callable $next): Response
    {
        $response = $next($request);
        return $response->withBody($response->body() . $this->append);
    }
}

class MiddlewarePipelineTest extends TestCase
{
    public function testPipelineExecution(): void
    {
        $pipeline = new MiddlewarePipeline();
        
        $pipeline->pipe(new DummyMiddleware('A'));
        $pipeline->pipe(new DummyMiddleware('B'));
        
        // Final handler
        $finalHandler = fn(RequestInterface $req) => new Response('Final');
        
        $request = Request::create('GET', '/');
        $response = $pipeline->process($request, $finalHandler);
        
        // Execution order: wrapMiddleware encadeia de trás para frente.
        // Middleware A chama next (que roda B) -> B chama next (Final).
        // Na volta: Final -> B adiciona B -> A adiciona A.
        // Logo o body fica "FinalBA".
        $this->assertEquals('FinalBA', $response->body());
    }
    
    public function testPipeCallable(): void
    {
        $pipeline = new MiddlewarePipeline();
        $pipeline->pipe(function (RequestInterface $request, callable $next) {
            $response = $next($request);
            return $response->withHeader('X-Callable', 'True');
        });
        
        $response = $pipeline->process(Request::create('GET', '/'), fn() => new Response(''));
        $this->assertEquals('True', $response->header('x-callable'));
    }
    
    public function testPipeManyAndCount(): void
    {
        $pipeline = new MiddlewarePipeline();
        $this->assertTrue($pipeline->isEmpty());
        
        $pipeline->pipeMany([
            new DummyMiddleware(),
            fn($req, $next) => $next($req)
        ]);
        
        $this->assertFalse($pipeline->isEmpty());
        $this->assertEquals(2, $pipeline->count());
    }
}
