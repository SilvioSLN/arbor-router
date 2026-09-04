<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Validation\Validator;
use Arbor\Router\Exception\ValidationException;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidDataPasses(): void
    {
        $data = ['email' => 'test@example.com', 'age' => 20];
        $rules = ['email' => 'required|email', 'age' => 'numeric|min:18'];
        
        $result = $this->validator->make($data, $rules);
        $this->assertTrue($result->passes());
    }

    public function testInvalidDataFailsWithMessages(): void
    {
        $data = ['email' => 'not-an-email', 'age' => 15];
        $rules = ['email' => 'required|email', 'age' => 'numeric|min:18'];
        
        $result = $this->validator->make($data, $rules);
        $this->assertTrue($result->fails());
        
        $errors = $result->errors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('age', $errors);
        $this->assertStringContainsString('valid email', $errors['email'][0]);
    }

    public function testValidateThrowsExceptionOnFail(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(['name' => ''], ['name' => 'required']);
    }
    
    public function testValidatesNestedArraysWithWildcard(): void
    {
        $data = [
            'items' => [
                ['price' => 10],
                ['price' => -5]
            ]
        ];
        
        $result = $this->validator->make($data, ['items.*.price' => 'numeric|min:0']);
        
        $this->assertTrue($result->fails());
        $this->assertArrayHasKey('items.1.price', $result->errors());
    }
}
