<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Http\Request;

class RequestTest extends TestCase
{
    public function testFromGlobalsCreatesValidRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users/123?active=true';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        
        $_GET['active'] = 'true';
        $_POST['name'] = 'Alice';
        
        $request = Request::fromGlobals();
        
        $this->assertEquals('POST', $request->method());
        $this->assertEquals('/users/123', $request->path());
        $this->assertEquals('application/json', $request->accept());
        $this->assertTrue($request->isAjax());
        
        $this->assertEquals('true', $request->query()['active']);
        $this->assertEquals('Alice', $request->body()['name']);
    }
    
    public function testBodyParsesJsonInput(): void
    {
        // Precisamos simular json no body. Como php://input não pode ser mockado facilmente
        // para testes via $_SERVER diretamente, podemos instanciar a classe Request diretamente.
        // O construtor não foi exposto publicamente com php://input injetável na versão original, 
        // mas vamos instanciar diretamente testando com POST.
        
        $request = \Arbor\Router\Http\Request::create('PUT', '/api', [], [], ['CONTENT_TYPE' => 'application/json']);
        
        // Reflection para injetar rawBody já que php://input é lido lazily
        $reflection = new \ReflectionClass($request);
        $property = $reflection->getProperty('cachedRawBody');
        $property->setAccessible(true);
        $property->setValue($request, '{"id": 42}');
        
        $body = $request->body();
        $this->assertEquals(['id' => 42], $body);
    }
    
    public function testHeaderRetrievalIsCaseInsensitive(): void
    {
        $request = Request::create('GET', '/', [], [], ['X-Custom-Header' => 'Value']);
        
        $this->assertEquals('Value', $request->header('x-custom-header'));
        $this->assertEquals('Value', $request->header('X-Custom-Header'));
        $this->assertNull($request->header('Missing'));
    }
}
