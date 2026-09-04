<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Rendering;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Rendering\LayoutResolver;

class LayoutResolverTest extends TestCase
{
    private string $fixtureDir;
    private LayoutResolver $resolver;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/arbor_router_layout_test';
        $this->cleanFixtures();
        mkdir($this->fixtureDir, 0777, true);
        $this->resolver = new LayoutResolver();
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

    public function testResolvesLayoutChain(): void
    {
        $this->createFixtureFile('layoutroot.php');
        $this->createFixtureFile('dashboard/layout.php');
        $this->createFixtureFile('dashboard/settings/layout.php');
        
        $result = $this->resolver->resolve($this->fixtureDir . '/dashboard/settings', $this->fixtureDir);
        
        $this->assertEquals($this->fixtureDir . '/layoutroot.php', $result['layoutRoot']);
        $this->assertCount(2, $result['layouts']);
        $this->assertEquals($this->fixtureDir . '/dashboard/layout.php', $result['layouts'][0]);
        $this->assertEquals($this->fixtureDir . '/dashboard/settings/layout.php', $result['layouts'][1]);
    }
    
    public function testGetRenderChainReversesArray(): void
    {
        $layouts = ['/app/a/b/layout.php', '/app/a/layout.php']; // Como vem do resolve() (folha -> raiz)
        $root = '/app/layoutroot.php';
        
        $chain = $this->resolver->getRenderChain($layouts, $root);
        
        $this->assertCount(3, $chain);
        $this->assertEquals($root, $chain[0]);
        $this->assertEquals('/app/a/b/layout.php', $chain[1]);
        $this->assertEquals('/app/a/layout.php', $chain[2]);
    }
}
