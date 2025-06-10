<?php

namespace LePostClient\Exceptions;

/**
 * Base exception class for LePostClient.
 * 
 * All specific exception types should extend this class to provide consistent
 * behavior and functionality across the plugin.
 */
abstract class BaseException extends \Exception implements ExceptionInterface 
{
    /**
     * Constructor.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null) 
    {
        parent::__construct($message, $code, $previous);
    }
} 