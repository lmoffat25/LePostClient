<?php

namespace LePostClient\Exceptions;

/**
 * Exception thrown when post generation operations fail.
 */
class PostGenerationException extends BaseException 
{
    /**
     * Create an exception for when post generation fails.
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
            sprintf('Post generation failed: %s', $message),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when a required field is missing.
     *
     * @param string $field The name of the missing field
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function missingField(string $field, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Required field "%s" is missing', $field),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when WordPress post creation fails.
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function wpInsertFailed(string $message, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('WordPress failed to create post: %s', $message),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when invalid arguments are provided.
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function invalidArguments(string $message, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Invalid arguments: %s', $message),
            $code,
            $previous
        );
    }
} 