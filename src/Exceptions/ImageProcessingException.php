<?php

namespace LePostClient\Exceptions;

/**
 * Exception thrown when image processing operations fail.
 */
class ImageProcessingException extends BaseException 
{
    /**
     * Create an exception for when image download fails.
     *
     * @param string $url The image URL
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function downloadFailed(string $url, string $message, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to download image from "%s": %s', $url, $message),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when image upload to media library fails.
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function uploadFailed(string $message, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to upload image to media library: %s', $message),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when image optimization fails.
     *
     * @param string $url The image URL
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function optimizationFailed(string $url, string $message, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to optimize image from "%s": %s', $url, $message),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when image compression fails.
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function compressionFailed(string $message, int $code = 0, \Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to compress image: %s', $message),
            $code,
            $previous
        );
    }
    
    /**
     * Create an exception for when image format conversion fails.
     *
     * @param string $from_format The source format
     * @param string $to_format The target format
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     * 
     * @return self
     */
    public static function conversionFailed(
        string $from_format, 
        string $to_format, 
        string $message, 
        int $code = 0, 
        \Throwable $previous = null
    ): self
    {
        return new self(
            sprintf('Failed to convert image from %s to %s: %s', $from_format, $to_format, $message),
            $code,
            $previous
        );
    }
} 