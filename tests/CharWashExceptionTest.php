<?php
declare(strict_types=1);

namespace OdinDev\CharWash\Tests;

use OdinDev\CharWash\Exceptions\CharWashException;
use PHPUnit\Framework\TestCase;

class CharWashExceptionTest extends TestCase
{
    public function testInvalidConfiguration(): void
    {
        $exception = CharWashException::invalidConfiguration('test error');

        $this->assertInstanceOf(CharWashException::class, $exception);
        $this->assertEquals('Invalid CharWash configuration: test error', $exception->getMessage());
    }

    public function testProcessorError(): void
    {
        $exception = CharWashException::processorError('HtmlProcessor', 'parsing failed');

        $this->assertInstanceOf(CharWashException::class, $exception);
        $this->assertEquals('Error in HtmlProcessor processor: parsing failed', $exception->getMessage());
    }

    public function testEncodingError(): void
    {
        $exception = CharWashException::encodingError('invalid UTF-8');

        $this->assertInstanceOf(CharWashException::class, $exception);
        $this->assertEquals('Encoding error: invalid UTF-8', $exception->getMessage());
    }

    public function testMissingDependency(): void
    {
        $exception = CharWashException::missingDependency('ext-intl');

        $this->assertInstanceOf(CharWashException::class, $exception);
        $this->assertEquals('Missing required dependency: ext-intl. Please install it via Composer.', $exception->getMessage());
    }
}