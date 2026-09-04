<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Http\Response;
use Arbor\Router\Http\HtmlResponse;
use Arbor\Router\Http\JsonResponse;
use Arbor\Router\Http\RedirectResponse;
use Arbor\Router\Http\TextResponse;
use Arbor\Router\Http\XmlResponse;

class ResponseTest extends TestCase
{
    public function testBaseResponseSendsHeadersAndContent(): void
    {
        $response = new Response('Hello World', 201, ['X-Custom' => 'Test']);
        
        $this->assertEquals('Hello World', $response->body());
        $this->assertEquals(201, $response->statusCode());
        $this->assertEquals('Test', $response->headers()['x-custom']);
    }
    
    public function testHtmlResponse(): void
    {
        $response = new HtmlResponse('<p>Hi</p>');
        $this->assertEquals('<p>Hi</p>', $response->body());
        $this->assertEquals('text/html; charset=utf-8', $response->headers()['content-type']);
    }
    
    public function testJsonResponseSerializesData(): void
    {
        $response = new JsonResponse(['ok' => true]);
        $this->assertEquals('{"ok":true}', $response->body());
        $this->assertStringContainsString('application/json', $response->headers()['content-type']);
    }
    
    public function testRedirectResponse(): void
    {
        $response = new RedirectResponse('/login', 301);
        $this->assertEquals(301, $response->statusCode());
        $this->assertEquals('/login', $response->headers()['location']);
    }
    
    public function testTextResponse(): void
    {
        $response = new TextResponse('Just text');
        $this->assertEquals('text/plain; charset=utf-8', $response->headers()['content-type']);
    }
    
    public function testXmlResponseSerializesArray(): void
    {
        $response = new XmlResponse(['user' => ['name' => 'Bob']]);
        
        $content = $response->body();
        $this->assertStringContainsString('<?xml version="1.0"', $content);
        $this->assertStringContainsString('<name>Bob</name>', $content);
        $this->assertStringContainsString('application/xml', $response->headers()['content-type']);
    }
}
