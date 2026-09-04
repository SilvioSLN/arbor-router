<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Routing\Route;
use Arbor\Router\Routing\RouteMap;
use Arbor\Router\Routing\RouteMatcher;
use Arbor\Router\Routing\RouteType;
use Arbor\Router\Routing\SegmentParser;

class RouteMatcherTest extends TestCase
{
    private RouteMap $routeMap;
    private RouteMatcher $matcher;

    protected function setUp(): void
    {
        $this->routeMap = new RouteMap();
        $this->matcher = new RouteMatcher($this->routeMap);
    }
    
    private function createRoute(string $pattern): Route
    {
        $segments = array_values(array_filter(explode('/', trim($pattern, '/'))));
        $defs = [];
        foreach ($segments as $seg) {
            $defs[$seg] = SegmentParser::parse($seg);
        }
        return new Route(
            type: RouteType::Page,
            filePath: '/app' . $pattern . '/page.php',
            directoryPath: '/app' . $pattern,
            urlPattern: $pattern,
            segments: $segments,
            segmentDefinitions: $defs
        );
    }

    public function testExactStaticMatch(): void
    {
        $route = $this->createRoute('/users');
        $this->routeMap->addRoute($route);

        $match = $this->matcher->matchAny('/users');
        $this->assertNotNull($match);
        $this->assertSame($route, $match['route']);
        $this->assertEmpty($match['params']);
        
        $this->assertNull($this->matcher->matchAny('/users/123'));
    }

    public function testDynamicMatch(): void
    {
        $route1 = $this->createRoute('/users');
        $route2 = $this->createRoute('/users/[id]');
        
        $this->routeMap->addRoute($route1);
        $this->routeMap->addRoute($route2);

        $match = $this->matcher->matchAny('/users/42');
        $this->assertNotNull($match);
        $this->assertSame($route2, $match['route']);
        $this->assertEquals(['id' => '42'], $match['params']);
    }

    public function testCatchAllMatch(): void
    {
        $route = $this->createRoute('/docs/[...slug]');
        $this->routeMap->addRoute($route);

        $this->assertNull($this->matcher->matchAny('/docs'));

        $match = $this->matcher->matchAny('/docs/getting-started/install');
        $this->assertNotNull($match);
        $this->assertEquals(['slug' => ['getting-started', 'install']], $match['params']);
    }
    
    public function testOptionalCatchAllMatch(): void
    {
        $route = $this->createRoute('/blog/[[...slug]]');
        $this->routeMap->addRoute($route);

        $match1 = $this->matcher->matchAny('/blog');
        $this->assertNotNull($match1);
        $this->assertEquals(['slug' => []], $match1['params']);

        $match2 = $this->matcher->matchAny('/blog/2023/php');
        $this->assertNotNull($match2);
        $this->assertEquals(['slug' => ['2023', 'php']], $match2['params']);
    }
    

}
