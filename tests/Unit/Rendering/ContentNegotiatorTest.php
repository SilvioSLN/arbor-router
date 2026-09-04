<?php

declare(strict_types=1);

namespace Arbor\Router\Tests\Unit\Rendering;

use PHPUnit\Framework\TestCase;
use Arbor\Router\Rendering\ContentNegotiator;

class ContentNegotiatorTest extends TestCase
{
    private ContentNegotiator $negotiator;

    protected function setUp(): void
    {
        $this->negotiator = new ContentNegotiator();
    }

    public function testDefaultFormat(): void
    {
        $this->assertEquals(ContentNegotiator::FORMAT_JSON, $this->negotiator->negotiate(null));
        $this->assertEquals(ContentNegotiator::FORMAT_JSON, $this->negotiator->negotiate(''));
        $this->assertEquals(ContentNegotiator::FORMAT_JSON, $this->negotiator->negotiate('*/*'));
    }

    public function testExactMatch(): void
    {
        $this->assertEquals(ContentNegotiator::FORMAT_JSON, $this->negotiator->negotiate('application/json'));
        $this->assertEquals(ContentNegotiator::FORMAT_XML, $this->negotiator->negotiate('text/xml'));
        $this->assertEquals(ContentNegotiator::FORMAT_HTML, $this->negotiator->negotiate('text/html'));
        $this->assertEquals(ContentNegotiator::FORMAT_TEXT, $this->negotiator->negotiate('text/plain'));
    }

    public function testQualityOrdering(): void
    {
        $header = 'text/html;q=0.8, application/json;q=0.9, text/plain;q=0.1';
        $this->assertEquals(ContentNegotiator::FORMAT_JSON, $this->negotiator->negotiate($header));
    }

    public function testWildcardGenericMatch(): void
    {
        $this->assertEquals(ContentNegotiator::FORMAT_JSON, $this->negotiator->negotiate('application/*'));
        $this->assertEquals(ContentNegotiator::FORMAT_JSON, $this->negotiator->negotiate('text/*')); // Text JSON map
    }

    public function testGetContentType(): void
    {
        $this->assertEquals('application/json; charset=utf-8', $this->negotiator->getContentType(ContentNegotiator::FORMAT_JSON));
        $this->assertEquals('text/html; charset=utf-8', $this->negotiator->getContentType(ContentNegotiator::FORMAT_HTML));
        $this->assertEquals('application/json; charset=utf-8', $this->negotiator->getContentType('unknown'));
    }
}
