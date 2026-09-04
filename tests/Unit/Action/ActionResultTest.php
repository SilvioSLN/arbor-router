<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Action;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Action\ActionResult;

class ActionResultTest extends TestCase
{
    public function testFluentApi(): void
    {
        $result = ActionResult::success('Ok')->data(['id' => 1])->statusCode(201);
        
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isError());
        $this->assertEquals('Ok', $result->getMessage());
        $this->assertEquals(['id' => 1], $result->getData());
        $this->assertEquals(201, $result->getHttpStatusCode());
    }

    public function testFromArray(): void
    {
        $result = ActionResult::fromArray([
            'success' => false,
            'message' => 'Failed',
            'errors' => ['email' => ['Invalid']],
            'status' => 400
        ]);
        
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Failed', $result->getMessage());
        $this->assertEquals(['email' => ['Invalid']], $result->getErrors());
        $this->assertEquals(400, $result->getHttpStatusCode());
    }

    public function testToArrayContainsExpectedKeys(): void
    {
        $result = ActionResult::success('Done')->redirect('/home');
        $array = $result->toArray();
        
        $this->assertTrue($array['success']);
        $this->assertEquals('Done', $array['message']);
        $this->assertEquals('/home', $array['redirect']);
        $this->assertArrayNotHasKey('errors', $array);
        $this->assertArrayNotHasKey('data', $array);
    }
}
