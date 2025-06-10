<?php

namespace LePostClient\Exceptions;

/**
 * Interface for all LePostClient exceptions.
 * 
 * This serves as a marker interface for all exceptions thrown by the LePostClient plugin,
 * allowing for catching all plugin-specific exceptions with a single catch block.
 */
interface ExceptionInterface extends \Throwable 
{
    // This empty interface serves as a marker for all LePostClient exceptions
} 