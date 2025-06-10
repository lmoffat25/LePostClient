# Exception Handling in LePostClient

This document provides an overview of the exception handling system implemented in the LePostClient WordPress plugin.

## Exception Class Hierarchy

All custom exceptions extend from a base hierarchy:

- `\LePostClient\Exceptions\ExceptionInterface` - Marker interface for all plugin exceptions
- `\LePostClient\Exceptions\BaseException` - Abstract base class extending PHP's `\Exception`

The specific exception types include:

- `ApiException` - For API communication errors
- `ContentGenerationException` - For content generation errors
- `ImageProcessingException` - For image processing errors
- `PostGenerationException` - For post creation errors

## Named Constructors

Each exception class uses named constructors (static factory methods) to create semantically meaningful exceptions:

```php
// Example usage:
throw ApiException::requestFailed('endpoint', 'Error message');
throw ContentGenerationException::missingContent();
throw ImageProcessingException::downloadFailed($url, $error_message);
throw PostGenerationException::missingField('subject');
```

## Global Exception Handler

A global exception handler has been implemented in `lepostclient.php` that:

1. Logs all uncaught exceptions to the WordPress error log
2. Displays admin notices for exceptions when in the admin area
3. Handles AJAX requests gracefully

## Exception Handling Strategy

The plugin follows these principles for exception handling:

1. **Throw specific exceptions** at the point of failure with contextual information
2. **Catch exceptions** as close as possible to where they can be meaningfully handled
3. **Convert exceptions to WP_Error** objects when returning to WordPress core functions
4. **Log all exceptions** with detailed information for debugging
5. **Provide user-friendly error messages** in the admin interface

## Key Implementation Areas

1. **API Client (`src/Api/Client.php`)** - Handles API communication errors
2. **Post Generator (`src/Post/Generator.php`)** - Handles content generation and post creation errors
3. **Post Assembler (`src/Post/PostAssembler.php`)** - Handles content assembly errors
4. **Core Plugin (`src/Core/Plugin.php`)** - Handles cron job and initialization errors

## Benefits

This exception handling system provides several benefits:

1. **Improved debugging** with detailed error information
2. **Cleaner code** by separating error handling from business logic
3. **Better error recovery** with specific error types and contexts
4. **Consistent error logging** across the entire plugin
5. **Graceful degradation** when errors occur

## Best Practices for Extending

When adding new features to the plugin:

1. Create specific exception types for new error domains
2. Use named constructors to create meaningful error messages
3. Catch exceptions at the appropriate level of abstraction
4. Always log exceptions for debugging
5. Convert exceptions to WordPress-friendly formats (WP_Error) at integration points 