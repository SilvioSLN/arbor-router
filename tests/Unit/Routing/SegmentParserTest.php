<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Routing\SegmentParser;

class SegmentParserTest extends TestCase
{
    public function testStaticSegments(): void
    {
        $this->assertFalse(SegmentParser::isDynamic('users'));
        $this->assertFalse(SegmentParser::isGroup('users'));
        
        $parsed = SegmentParser::parse('users');
        $this->assertEquals(SegmentParser::TYPE_STATIC, $parsed['type']);
        $this->assertEquals('users', $parsed['name']);
    }

    public function testGroupSegments(): void
    {
        $this->assertTrue(SegmentParser::isGroup('(admin)'));
        $this->assertFalse(SegmentParser::isGroup('admin'));
        
        $parsed = SegmentParser::parse('(admin)');
        $this->assertEquals(SegmentParser::TYPE_GROUP, $parsed['type']);
        $this->assertEquals('admin', $parsed['name']);
    }

    public function testDynamicSegments(): void
    {
        $this->assertTrue(SegmentParser::isDynamic('[id]'));
        $this->assertTrue(SegmentParser::isDynamic('[...slug]'));
        $this->assertTrue(SegmentParser::isDynamic('[[...slug]]'));
        
        $this->assertFalse(SegmentParser::isDynamic('id'));
        
        $parsedParam = SegmentParser::parse('[id]');
        $this->assertEquals(SegmentParser::TYPE_DYNAMIC, $parsedParam['type']);
        $this->assertEquals('id', $parsedParam['name']);
        
        $parsedCatch = SegmentParser::parse('[...slug]');
        $this->assertEquals(SegmentParser::TYPE_CATCH_ALL, $parsedCatch['type']);
        $this->assertEquals('slug', $parsedCatch['name']);
        
        $parsedOpt = SegmentParser::parse('[[...slug]]');
        $this->assertEquals(SegmentParser::TYPE_OPTIONAL_CATCH_ALL, $parsedOpt['type']);
        $this->assertEquals('slug', $parsedOpt['name']);
    }
    
    public function testDirectoryToUrlSegmentsIgnoresGroups(): void
    {
        $segments = SegmentParser::directoryToUrlSegments('/app/users/(admin)/settings', '/app');
        $this->assertEquals(['users', 'settings'], $segments);
    }
}
