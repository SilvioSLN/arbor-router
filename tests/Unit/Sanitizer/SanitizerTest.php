<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Sanitizer;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Sanitizer\Sanitizer;

class SanitizerTest extends TestCase
{
    private Sanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new Sanitizer();
    }

    public function testSanitizerAppliesFilters(): void
    {
        $data = [
            'name' => '  John Doe  ',
            'html' => '<p>Test</p>',
            'amount' => 'R$ 1.500,99', // com numeric vai virar 1500.99
            'title' => 'My Cool Title'
        ];
        
        $rules = [
            'name' => 'trim|uppercase',
            'html' => 'strip_tags',
            'amount' => 'numeric',
            'title' => 'slug'
        ];
        
        $sanitized = $this->sanitizer->sanitize($data, $rules);
        
        $this->assertEquals('JOHN DOE', $sanitized['name']);
        $this->assertEquals('Test', $sanitized['html']);
        $this->assertEquals(1.50099, (float) $sanitized['amount']);
        $this->assertEquals('my-cool-title', $sanitized['title']);
    }
}
