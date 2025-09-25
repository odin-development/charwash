<?php
declare(strict_types=1);

namespace OdinDev\CharWash\Exceptions;

use Exception;

/**
 * CharWashException - Package-specific exception class
 */
class CharWashException extends Exception
{
    /**
     * Create exception for invalid configuration
     *
     * @param string $message The error message
     * @return self
     */
    public static function invalidConfiguration(string $message): self
    {
        return new self("Invalid CharWash configuration: {$message}");
    }

    /**
     * Create exception for processor error
     *
     * @param string $processor The processor name
     * @param string $message The error message
     * @return self
     */
    public static function processorError(string $processor, string $message): self
    {
        return new self("Error in {$processor} processor: {$message}");
    }

    /**
     * Create exception for encoding error
     *
     * @param string $message The error message
     * @return self
     */
    public static function encodingError(string $message): self
    {
        return new self("Encoding error: {$message}");
    }

    /**
     * Create exception for missing dependency
     *
     * @param string $dependency The missing dependency
     * @return self
     */
    public static function missingDependency(string $dependency): self
    {
        return new self("Missing required dependency: {$dependency}. Please install it via Composer.");
    }
}