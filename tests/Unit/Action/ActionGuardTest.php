<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Action;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Action\ActionGuard;
use Arbor\Router\Http\Request;
use Arbor\Router\Exception\ForbiddenException;
use Arbor\Router\Exception\ActionOriginMismatchException;

class ActionGuardTest extends TestCase
{
    private ActionGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new ActionGuard('X-Action-Request', 'true', null); // Sem CSRF aqui
    }

    public function testRejectsGetMethod(): void
    {
        $request = Request::create('GET', '/users', [], [], ['X-Action-Request' => 'true']);
        $this->expectException(ForbiddenException::class);
        $this->guard->validate($request, '/users');
    }

    public function testAllowsMissingHeaderForHtmlForms(): void
    {
        $request = Request::create('POST', '/users', [], [], []);
        $this->guard->validate($request, '/users');
        $this->assertTrue(true);
    }

    public function testRejectsInvalidHeaderValue(): void
    {
        $request = Request::create('POST', '/users', [], [], ['X-Action-Request' => 'invalid']);
        $this->expectException(ForbiddenException::class);
        $this->guard->validate($request, '/users');
    }

    public function testRejectsOriginMismatch(): void
    {
        $request = Request::create('POST', '/users', [], [], ['X-Action-Request' => 'true']);
        $this->expectException(ActionOriginMismatchException::class);
        $this->guard->validate($request, '/admin');
    }

    public function testAcceptsValidRequest(): void
    {
        $request = Request::create('POST', '/users', [], [], ['X-Action-Request' => 'true']);
        
        // Nenhuma exceção deve ser lançada
        $this->guard->validate($request, '/users');
        $this->assertTrue(true);
    }
}
