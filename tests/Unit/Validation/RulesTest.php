<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Validation\Rules\RequiredRule;
use Arbor\Router\Validation\Rules\EmailRule;
use Arbor\Router\Validation\Rules\MinRule;
use Arbor\Router\Validation\Rules\MaxRule;

class RulesTest extends TestCase
{
    public function testRequiredRule(): void
    {
        $rule = new RequiredRule();
        $this->assertTrue($rule->validate('abc', [], 'f', []));
        $this->assertFalse($rule->validate('', [], 'f', []));
        $this->assertFalse($rule->validate(null, [], 'f', []));
        $this->assertFalse($rule->validate([], [], 'f', []));
    }

    public function testEmailRule(): void
    {
        $rule = new EmailRule();
        $this->assertTrue($rule->validate('test@example.com', [], 'f', []));
        $this->assertFalse($rule->validate('not-email', [], 'f', []));
    }

    public function testMinMaxRules(): void
    {
        $min = new MinRule();
        $max = new MaxRule();
        
        // String
        $this->assertTrue($min->validate('abc', ['3'], 'f', []));
        $this->assertFalse($min->validate('ab', ['3'], 'f', []));
        
        // Numeric
        $this->assertTrue($min->validate(5, ['5'], 'f', []));
        $this->assertTrue($max->validate(5, ['5'], 'f', []));
        $this->assertFalse($max->validate(6, ['5'], 'f', []));
        
        // Array
        $this->assertTrue($min->validate([1,2,3], ['3'], 'f', []));
        $this->assertFalse($min->validate([1,2], ['3'], 'f', []));
    }
}
