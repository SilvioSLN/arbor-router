<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Routing\RouteScanner;

class RouteScannerTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/arbor_router_scanner_test';
        $this->cleanFixtures();
        mkdir($this->fixtureDir, 0777, true);
    }
    
    protected function tearDown(): void
    {
        $this->cleanFixtures();
    }
    
    private function cleanFixtures(): void
    {
        if (is_dir($this->fixtureDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->fixtureDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            rmdir($this->fixtureDir);
        }
    }
    
    private function createFixtureFile(string $relativePath): void
    {
        $fullPath = $this->fixtureDir . '/' . $relativePath;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($fullPath, '<?php // test');
    }

    public function testScannerFindsPagesAndApis(): void
    {
        $this->createFixtureFile('page.php');
        $this->createFixtureFile('users/page.php');
        $this->createFixtureFile('api/status/route.php');
        $this->createFixtureFile('action.php'); // Na raiz
        $this->createFixtureFile('(auth)/login/page.php'); // Grupo
        
        $scanner = new RouteScanner($this->fixtureDir);
        $routeMap = $scanner->scan();
        $routes = $routeMap->all();
        
        $this->assertCount(5, $routes);
        
        // Checar grupos
        $loginRoute = array_filter($routes, fn($r) => $r->urlPattern === '/login');
        $this->assertCount(1, $loginRoute);
        
        // Checar action
        $actionRoute = array_filter($routes, fn($r) => $r->urlPattern === '/' && $r->type === \Arbor\Router\Routing\RouteType::Action);
        $this->assertCount(1, $actionRoute);
    }
    
    public function testScannerResolvesLayoutsAndMiddleware(): void
    {
        $this->createFixtureFile('layoutroot.php');
        $this->createFixtureFile('middleware.php');
        $this->createFixtureFile('dashboard/layout.php');
        $this->createFixtureFile('dashboard/middleware.php');
        $this->createFixtureFile('dashboard/page.php');
        
        $scanner = new RouteScanner($this->fixtureDir);
        $routes = $scanner->scan()->all();
        
        $this->assertCount(1, $routes);
        $route = $routes[0];
        
        $this->assertEquals('/dashboard', $route->urlPattern);
        $this->assertNotNull($route->layoutRootFile);
        $this->assertCount(1, $route->layoutFiles); // dashboard/layout.php
        $this->assertCount(2, $route->middlewareFiles); // /middleware.php e /dashboard/middleware.php
    }
}
