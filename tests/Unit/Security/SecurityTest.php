<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Security\SecurityManager;
use Arbor\Router\Security\ApiGuard;
use Arbor\Router\Security\CsrfGuard;
use Arbor\Router\Http\Request;
use Arbor\Router\Exception\ForbiddenException;
use Arbor\Router\Exception\CsrfTokenMismatchException;

class SecurityTest extends TestCase
{
    public function testSecurityManagerReturnsHeaders(): void
    {
        $manager = new SecurityManager(['X-Custom' => 'Value']);
        $headers = $manager->getHeaders();
        
        $this->assertEquals('nosniff', $headers['X-Content-Type-Options']);
        $this->assertEquals('Value', $headers['X-Custom']);
    }
    
    public function testApiGuardValidatesHeader(): void
    {
        $guard = new ApiGuard('X-API', 'yes');
        
        $this->expectException(ForbiddenException::class);
        $request = Request::create('GET', '/', [], [], []);
        $guard->validate($request);
    }
    
    public function testApiGuardPassesWithValidHeader(): void
    {
        $guard = new ApiGuard('X-API', 'yes');
        $request = Request::create('GET', '/', [], [], ['X-API' => 'yes']);
        
        $guard->validate($request);
        $this->assertTrue(true);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCsrfGuardGeneratesAndValidates(): void
    {
        // runInSeparateProcess eh necessário porque mexe com sessions
        session_start();
        $guard = new CsrfGuard();
        $token = $guard->getToken();
        
        $this->assertNotEmpty($token);
        
        // Passa se o token estiver no header
        $request = Request::create('POST', '/', [], [], ['X-CSRF-Token' => $token]);
        $guard->validate($request);
        
        // Falha se tiver token incorreto
        $badRequest = Request::create('POST', '/', [], [], ['X-CSRF-Token' => 'wrong']);
        $this->expectException(CsrfTokenMismatchException::class);
        $guard->validate($badRequest);
    }
}
