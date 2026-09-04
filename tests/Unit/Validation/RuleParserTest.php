<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Validation\RuleParser;

class RuleParserTest extends TestCase
{
    private RuleParser $parser;

    protected function setUp(): void
    {
        $this->parser = new RuleParser();
    }

    public function testParsesPipeStrings(): void
    {
        $rules = $this->parser->parse('required|min:3|max:10|in:a,b,c');
        
        $this->assertCount(4, $rules);
        $this->assertEquals('required', $rules[0]['name']);
        $this->assertEmpty($rules[0]['parameters']);
        
        $this->assertEquals('min', $rules[1]['name']);
        $this->assertEquals(['3'], $rules[1]['parameters']);
        
        $this->assertEquals('in', $rules[3]['name']);
        $this->assertEquals(['a', 'b', 'c'], $rules[3]['parameters']);
    }

    public function testExpandsDotNotationForWildcards(): void
    {
        $data = [
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob']
            ]
        ];
        
        $expanded = $this->parser->expandDotNotation('users.*.name', $data);
        
        $this->assertCount(2, $expanded);
        $this->assertArrayHasKey('users.0.name', $expanded);
        $this->assertArrayHasKey('users.1.name', $expanded);
        $this->assertEquals('Alice', $expanded['users.0.name']);
        $this->assertEquals('Bob', $expanded['users.1.name']);
    }
}
