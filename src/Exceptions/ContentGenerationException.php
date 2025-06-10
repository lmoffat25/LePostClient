<?php

namespace LePostClient\Exceptions;

/**
 * Exception thrown when content generation operations fail.
 */
class ContentGenerationException extends BaseException 
{
    /**
     * Create an exception for when content generation fails.
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function failed(string $message, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Content generation failed: %s', $message),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when API response is missing expected content.
     *
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function missingContent(int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            'API response does not contain expected content',
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when the API response format is invalid.
     *
     * @param string $endpoint The API endpoint
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function invalidResponse(string $endpoint, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Invalid response format received from endpoint "%s"', $endpoint),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when the content is malformed or invalid.
     *
     * @param string $message The specific issue with the content
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function malformedContent(string $message, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Malformed content received: %s', $message),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when the generated content is empty.
     *
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function emptyContent(int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            'Generated content is empty',
            $code,
            $previous
        );
    }
} 