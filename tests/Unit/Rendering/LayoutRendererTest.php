<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Rendering;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Rendering\LayoutRenderer;

class LayoutRendererTest extends TestCase
{
    private string $fixtureDir;
    private LayoutRenderer $renderer;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/arbor_router_renderer_test';
        if (!is_dir($this->fixtureDir)) mkdir($this->fixtureDir, 0777, true);
        $this->renderer = new LayoutRenderer();
    }
    
    protected function tearDown(): void
    {
        array_map('unlink', glob("$this->fixtureDir/*.*"));
        rmdir($this->fixtureDir);
    }
    
    public function testRenderFileExtractsVars(): void
    {
        $file = $this->fixtureDir . '/test.php';
        file_put_contents($file, '<?php echo $title; ?> - <?= $user ?>');
        
        $output = $this->renderer->renderFile($file, ['title' => 'Hello', 'user' => 'John']);
        $this->assertEquals('Hello - John', $output);
    }
    
    public function testRenderWithLayoutsNestsCorrectly(): void
    {
        $rootFile = $this->fixtureDir . '/root.php';
        $layoutFile = $this->fixtureDir . '/layout.php';
        
        file_put_contents($rootFile, '<html><?= $children ?></html>');
        file_put_contents($layoutFile, '<main><?= $children ?></main>');
        
        $pageContent = '<p>Page</p>';
        $chain = [$rootFile, $layoutFile]; // Outer first
        
        $final = $this->renderer->renderWithLayouts($pageContent, $chain, []);
        $this->assertEquals('<html><main><p>Page</p></main></html>', $final);
    }
}
