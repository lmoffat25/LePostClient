<?php

namespace LePostClient\Exceptions;

/**
 * Exception thrown when API operations fail.
 */
class ApiException extends BaseException 
{
    /**
     * Create an exception for when an API request fails.
     *
     * @param string $endpoint The API endpoint that was called
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function requestFailed(string $endpoint, string $message, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('API request to endpoint "%s" failed: %s', $endpoint, $message),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when an API response is invalid.
     *
     * @param string $endpoint The API endpoint that was called
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function invalidResponse(string $endpoint, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Invalid response received from endpoint "%s"', $endpoint),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when an API key is missing.
     *
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function missingApiKey(int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            'API key is missing or empty',
            $code,
            $previous
        );
    }
} 